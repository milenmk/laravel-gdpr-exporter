<?php

declare(strict_types=1);

namespace Milenmk\LaravelGdprExporter\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class UserDataExporterService
{
    public function exportToJson(Model|Authenticatable|null $user): string
    {
        $user = $this->validateAndResolveUser($user);
        $data = $this->prepareUserData($user);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function prepareUserData(Model $user): array
    {
        $relations = $this->getLoadableRelations($user);
        $user->load($relations);

        $array = $user->toArray();

        if (config('gdpr-exporter.export.remove_ids', true)) {
            $array = $this->removeIdsFromArray($array);
        }

        if (config('gdpr-exporter.export.flatten_pivot', true)) {
            $array = $this->flattenPivotInArray($array);
        }

        return $array;
    }

    public function getLoadableRelations(Model $user): array
    {
        $detectionMethod = config('gdpr-exporter.relations_detection.method', 'whitelist');

        return match ($detectionMethod) {
            'whitelist' => $this->getWhitelistedRelations(),
            'reflection' => $this->getRelationsByReflection($user),
            default => throw new InvalidArgumentException("Invalid relations detection method: {$detectionMethod}. Use 'whitelist' or 'reflection'.")
        };
    }

    public function exportToCsv(Model|Authenticatable|null $user): StreamedResponse
    {
        $user = $this->validateAndResolveUser($user);
        $data = $this->prepareUserData($user);
        $flat = $this->flattenArray($data);

        return Response::streamDownload(
            function () use ($flat) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Field', 'Value']);

                foreach ($flat as $key => $value) {
                    if (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    } elseif (is_array($value)) {
                        $value = json_encode($value);
                    }

                    fputcsv($handle, [$key, $value]);
                }

                fclose($handle);
            },
            'user-data.csv',
            ['Content-Type' => 'text/csv'],
        );
    }

    public function exportToXml(Model|Authenticatable|null $user): string
    {
        $user = $this->validateAndResolveUser($user);
        $data = $this->prepareUserData($user);
        $xml = $this->arrayToXml($data);

        return $xml->asXML();
    }

    public function exportToHtml(Model|Authenticatable|null $user): string
    {
        $user = $this->validateAndResolveUser($user);
        $data = $this->prepareUserData($user);

        return $this->arrayToHtml($data);
    }

    /**
     * Validate and resolve the user instance
     */
    private function validateAndResolveUser(Model|Authenticatable|null $user): Model
    {
        if ($user === null) {
            throw new InvalidArgumentException('User cannot be null. Make sure the user is authenticated.');
        }

        if ($user instanceof Model) {
            return $user;
        }

        // If it's an Authenticatable but not a Model, we need to resolve it
        if ($user instanceof Authenticatable) {
            $userModelClass = config('gdpr-exporter.user_model', 'App\Models\User');

            if (! class_exists($userModelClass)) {
                throw new InvalidArgumentException("User model class '{$userModelClass}' does not exist. Please check your gdpr-exporter.user_model configuration.");
            }

            // Try to find the user by ID if available
            if (method_exists($user, 'getAuthIdentifier')) {
                $userId = $user->getAuthIdentifier();
                $resolvedUser = $userModelClass::find($userId);

                if ($resolvedUser === null) {
                    throw new InvalidArgumentException("Could not resolve user with ID '{$userId}' using model '{$userModelClass}'.");
                }

                return $resolvedUser;
            }
        }

        throw new InvalidArgumentException('User must be an instance of Illuminate\Database\Eloquent\Model or a resolvable Authenticatable instance.');
    }

    /**
     * Get relations from the configured whitelist
     */
    private function getWhitelistedRelations(): array
    {
        return config('gdpr-exporter.relations_detection.whitelist', []);
    }

    /**
     * Get relations by reflection (safer version)
     */
    private function getRelationsByReflection(Model $user): array
    {
        $relations = [];
        $reflected = new ReflectionClass($user);
        $excludedMethods = config('gdpr-exporter.relations_detection.excluded_methods', []);

        foreach ($reflected->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip if method is not from the user class
            if ($method->class !== get_class($user)) {
                continue;
            }

            // Skip if method requires parameters
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            // Skip excluded methods for safety
            if (in_array($method->name, $excludedMethods, true)) {
                continue;
            }

            // Skip magic methods and common non-relation methods
            if ($this->shouldSkipMethod($method->name)) {
                continue;
            }

            try {
                $result = $method->invoke($user);
                if ($result instanceof Relation) {
                    // Don't execute the query here, just verify it's a relation
                    $relations[] = $method->name;
                }
            } catch (Throwable) {
                // Skip methods that throw errors
                continue;
            }
        }

        return $relations;
    }

    /**
     * Check if a method should be skipped during relation detection
     */
    private function shouldSkipMethod(string $methodName): bool
    {
        // Skip magic methods
        if (str_starts_with($methodName, '__')) {
            return true;
        }

        // Skip common Eloquent methods that aren't relations
        $commonMethods = [
            'getKey', 'getKeyName', 'getTable', 'getConnection', 'newQuery',
            'toArray', 'toJson', 'jsonSerialize', 'getAttributes', 'getDirty',
            'getOriginal', 'getChanges', 'getCasts', 'getDates', 'getDateFormat',
            'getCreatedAtColumn', 'getUpdatedAtColumn', 'getDeletedAtColumn',
            'getRouteKey', 'getRouteKeyName', 'resolveRouteBinding', 'getIncrementing',
            'usesTimestamps', 'touch', 'fresh', 'refresh', 'replicate', 'is', 'isNot',
            'getQueueableId', 'getQueueableRelations', 'getQueueableConnection',
            'exists', 'wasRecentlyCreated', 'wasChanged', 'isDirty', 'isClean',
            'getAuthIdentifierName', 'getAuthIdentifier', 'getAuthPassword',
            'getRememberToken', 'setRememberToken', 'getRememberTokenName',
        ];

        return in_array($methodName, $commonMethods, true);
    }

    private function removeIdsFromArray(array $array): array
    {
        foreach ($array as $key => &$value) {
            $keyStr = (string) $key;

            if ($keyStr === 'id' || str_ends_with($keyStr, '_by') || str_ends_with($keyStr, '_id')) {
                unset($array[$key]);
            } elseif (is_array($value)) {
                $value = $this->removeIdsFromArray($value);
            }
        }

        return $array;
    }

    private function flattenPivotInArray(array $array): array
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                if ($key === 'pivot' && Arr::isAssoc($value)) {
                    unset($array[$key]);
                    $array = array_merge($array, $value);
                } else {
                    $value = $this->flattenPivotInArray($value);
                }
            }
        }

        return $array;
    }

    // Helpers

    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix === '' ? $key : "$prefix.$key";

            if (is_array($value) && Arr::isAssoc($value)) {
                if ($key === 'pivot') {
                    foreach ($value as $pivotKey => $pivotValue) {
                        $result["$prefix$pivotKey"] = $pivotValue;
                    }
                } else {
                    $result += $this->flattenArray($value, $fullKey);
                }
            } elseif (is_array($value)) {
                foreach ($value as $index => $item) {
                    if (is_array($item)) {
                        $result += $this->flattenArray($item, "{$fullKey}[$index]");
                    } else {
                        $result["{$fullKey}[$index]"] = $item;
                    }
                }
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }

    private function arrayToXml(array $data, ?SimpleXMLElement $xml = null): SimpleXMLElement
    {
        if ($xml === null) {
            $xml = new SimpleXMLElement('<user/>');
        }

        foreach ($data as $key => $value) {
            $key = is_numeric($key) ? 'item' . $key : $key;

            if (is_array($value)) {
                if ($key === 'pivot') {
                    foreach ($value as $pivotKey => $pivotValue) {
                        if (is_bool($pivotValue)) {
                            $pivotValue = $pivotValue ? 'true' : 'false';
                        }
                        $xml->addChild($pivotKey, htmlspecialchars((string) $pivotValue));
                    }
                } else {
                    $child = $xml->addChild($key);
                    $this->arrayToXml($value, $child);
                }
            } else {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }

        return $xml;
    }

    private function arrayToHtml(array $data): string
    {
        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>User Data</title>';
        $html .= '<style>body{font-family:sans-serif}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:8px}</style>';
        $html .= '</head><body>';
        $html .= '<h1>User Data</h1>';
        $html .= $this->renderHtmlTable($data);
        $html .= '</body></html>';

        return $html;
    }

    private function renderHtmlTable(array $data): string
    {
        $html = '<table>';

        foreach ($data as $key => $value) {
            if ($key === 'pivot' && is_array($value)) {
                foreach ($value as $pivotKey => $pivotValue) {
                    $html .= '<tr>';
                    $html .= '<th>' . htmlspecialchars((string) $pivotKey) . '</th>';

                    if (is_bool($pivotValue)) {
                        $pivotValue = $pivotValue ? 'true' : 'false';
                    } elseif (is_array($pivotValue)) {
                        $pivotValue = $this->renderHtmlTable($pivotValue);
                        $html .= '<td>' . $pivotValue . '</td>';
                        $html .= '</tr>';

                        continue;
                    }

                    $html .= '<td>' . htmlspecialchars((string) $pivotValue) . '</td>';
                    $html .= '</tr>';
                }

                continue;
            }

            $html .= '<tr>';
            $html .= '<th>' . htmlspecialchars((string) $key) . '</th>';

            if (is_array($value)) {
                $html .= '<td>' . $this->renderHtmlTable($value) . '</td>';
            } else {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $html .= '<td>' . htmlspecialchars((string) $value) . '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }
}
