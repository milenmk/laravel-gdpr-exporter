<?php

declare(strict_types=1);

namespace Milenmk\LaravelGdprExporter\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use ReflectionClass;
use ReflectionMethod;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class UserDataExporterService
{
    public function exportToJson(User $user): string
    {
        $data = $this->prepareUserData($user);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function prepareUserData(User $user): array
    {
        $relations = $this->getLoadableRelations($user);
        $user->load($relations);

        $array = $user->toArray();
        $array = $this->removeIdsFromArray($array);
        return $this->flattenPivotInArray($array);
    }

    public function getLoadableRelations(User $user): array
    {
        $relations = [];
        $reflected = new ReflectionClass($user);

        foreach ($reflected->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== get_class($user)) {
                continue;
            }

            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            try {
                $result = $method->invoke($user);
                if ($result instanceof Relation) {
                    $result->getResults();
                    $relations[] = $method->name;
                }
            } catch (Throwable) {
                // Skip methods that throw errors
                continue;
            }
        }

        return $relations;
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

    public function exportToCsv(User $user): StreamedResponse
    {
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

    public function exportToXml(User $user): string
    {
        $data = $this->prepareUserData($user);
        $xml = $this->arrayToXml($data);

        return $xml->asXML();
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

    public function exportToHtml(User $user): string
    {
        $data = $this->prepareUserData($user);

        return $this->arrayToHtml($data);
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