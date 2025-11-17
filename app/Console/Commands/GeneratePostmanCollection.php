<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\Unique;
use ReflectionException;
use ReflectionMethod;
use ReflectionClass;

class GeneratePostmanCollection extends Command
{
    protected $signature = 'postman:generate
                            {--update : Update existing collection}
                            {--output=postman_collection.json : Output file name}';
    protected $description = 'Generate Postman collection from Laravel routes';

    public function handle(): void
    {
        $collection = $this->option('update')
            ? $this->updateExistingCollection()
            : $this->createNewCollection();

        file_put_contents(base_path($this->option('output')), json_encode($collection, JSON_PRETTY_PRINT));
        $this->info('Postman collection generated successfully!');
    }

    private function updateExistingCollection(): array
    {
        return [];
    }

    private function createNewCollection(): array
    {
        return [
            'info' => [
                'id' => '31977773-79c30d79-8edf-422b-b3ab-a21b0281e4b9' ?? Str::uuid()->toString(), // Add the UUID here
                'original_id' => '31977773-79c30d79-8edf-422b-b3ab-a21b0281e4b9', // Your custom identifier
                'name' => config('app.name') . ' API',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'variable' => [
                [
                    "key" => "URL",
                    "description" => "staging",
                    "enabled" => true,
                    "value" => config('app.host.staging'),
                    "sessionValue" => config('app.host.staging'),
                    "init_value" => "staging",
                ],
                [
                    "key" => "URL",
                    "description" => "Production",
                    "enabled" => false,
                    "value" => config('app.host.production')
                ],
                [
                    "key" => "URL",
                    "description" => "Tester",
                    "enabled" => false,
                    "value" => config('app.host.tester')
                ],
                [
                    "key" => "URL",
                    "description" => "Local",
                    "enabled" => false,
                    "value" => config('app.host.local')
                ],
                [
                    "key" => "admin_token",
                    "description" => "",
                    "value" => ""
                ]

            ],
            'item' => $this->generateFolderStructure(),
            'event' => [
                $this->getTestScript()
            ]
        ];
    }

    private function generateFolderStructure(): array
    {
        $structure = [];
        foreach (Route::getRoutes() as $route) {
            if (str_contains($route->uri(), 'telescope')) {
                continue;
            }

            $controllerAction = $route->getAction('controller');
            if (!str_contains($controllerAction, '@')) {
                continue;
            }

            [$controllerClass, $method] = explode('@', $controllerAction);

            $namespaceSegments = explode('\\', $controllerClass);
            $controllerName = array_pop($namespaceSegments); // Get the controller name (e.g., AdminController)
            $namespace = implode('\\', $namespaceSegments); // Get the namespace (e.g., App\Http\Controllers\Api\dashboard)

            $controllerName = str_replace('Controller', '', $controllerName);
            $namespace = str_replace('App\Http\Controllers\\', '', $namespace);

            $folders = explode('\\', $namespace);

            $currentLevel = &$structure;

            foreach ($folders as $folder) {
                if ($folder === 'Api') {
                    continue; // Skip the "Api" prefix
                }

                $found = false;
                foreach ($currentLevel as &$item) {
                    if ($item['name'] === $folder) {
                        $currentLevel = &$item['item'];
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $newItem = [
                        'name' => $folder,
                        'item' => [],
                    ];
                    $currentLevel[] = $newItem;
                    $currentLevel = &$currentLevel[array_key_last($currentLevel)]['item'];
                }
            }

            $found = false;
            foreach ($currentLevel as &$item) {
                if ($item['name'] === $controllerName) {
                    $currentLevel = &$item['item'];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $newItem = [
                    'name' => $controllerName,
                    'item' => [],
                ];
                $currentLevel[] = $newItem;
                $currentLevel = &$currentLevel[array_key_last($currentLevel)]['item'];
            }

            $currentLevel[] = $this->createRequestItem($route);

            if (empty($folders)) {
                $currentLevel[array_key_last($currentLevel)] = array_merge(
                    $currentLevel[array_key_last($currentLevel)],
                    [
                        'auth' => [
                            'type' => 'bearer',
                            'bearer' => [
                                [
                                    'key' => 'token',
                                    'value' => '{{' . strtolower($controllerName) . '_token}}',
                                    'type' => 'string',
                                ]
                            ]
                        ]
                    ]
                );
            }
        }

        return array_values($structure); // Reset array keys
    }

    private function createRequestItem($route): array
    {
        $controllerAction = $route->getAction('controller');
        $methods = $route->methods();
        $uri = $this->processUri($route->uri());

        $name = preg_replace('/(?<!^)[A-Z]/', ' $0', (@last(explode('@', $route->getAction()['uses']))) ?: "");
        $title = Str::title(str_replace('.', ' ', $route->getName() ?: $name)) ?: $uri;

        $requestItem = [
            'name' => $title,
            'request' => [
                'method' => in_array($methods[0], ['PUT', 'put']) ? 'POST' : $methods[0],
                'header' => [],
                'body' => $this->getRequestBody($controllerAction, $methods[0], $title),
                'url' => $this->parseUrl($uri),
                'description' => $this->getRouteDescription($controllerAction)
            ],
            'response' => []
        ];

        if (str_contains($uri, 'login')) {
            $platform = explode('/', trim(str_replace(['/api', 'api'], '', $uri), '/'))[0];
            $requestItem['event'] = [
                [
                    'listen' => 'test',
                    'script' => [
                        'exec' => [
                            'pm.test("http response is ok 200  POST /admin/login login admin user only", function (){',
                            '    pm.response.to.have.status(200);',
                            '    const responseJson = pm.response.json();',
                            '    if(responseJson){',
                            '        pm.collectionVariables.set("' . $platform . '_token", responseJson.data.token);',
                            '        pm.expect(responseJson.data.token).to.be.a("string");',
                            '    }',
                            '});'
                        ],
                        'type' => 'text/javascript'
                    ]
                ]
            ];
        }

        return $requestItem;
    }

    private function processUri($uri): string
    {
        $processed = preg_replace('/\{(\w+?)\??\}/', ':$1Id', $uri);
        return 'api/' . $processed;
    }

    private function getRequestBody($controllerAction, $method, $title): array
    {
        $rules = $this->getValidationRules($controllerAction);

        $formData = collect($rules)->flatMap(function ($rule, $field) {
            $data = [
                [
                    'key' => $this->getFiled($field),
                    'value' => $this->getSampleValue($field),
                    'type' => $this->getFiledType($field, $rule),
                    'description' => $this->parseDescription($rule)
                ]
            ];

            if ((is_string($rule) and str_contains($rule, 'confirmed')) || (is_array($rule) and in_array('confirmed', $rule))) {
                $data[] = [
                    'key' => "{$field}_confirmation",
                    'value' => '',
                    'type' => 'text',
                    'description' => 'Confirmation field for ' . $field
                ];
            }

            return $data;
        })->values()->toArray();


        $methodFormData = [];
        if (in_array($method, ['PUT', 'put'])) {
            $methodFormData = [
                (count($formData) + 1) => [
                    'key' => '_method',
                    'value' => 'PUT',
                    'type' => 'text',
                    'description' => 'for laravel to identify the request method, is required when body has keys'
                ]
            ];
        }

        return [
            'mode' => 'formdata',
            'formdata' => count($methodFormData) ? array_merge($formData, $methodFormData) : $formData
        ];
    }

    private function getFiledType($field, $rule): string
    {
        return "text";
    }

    private function getFiled($field): string
    {
        if (str_contains($field, '.')) {
            $newField = explode('.', $field);
            $data = array_map(fn($i) => "[$i]", Arr::except($newField, [0]));
            return "{$newField[0]}" . join('', $data);
        }
        return $field;
    }

    private function getValidationRules($controllerAction)
    {
        if (!str_contains($controllerAction, '@')) {
            return [];
        }

        [$controller, $method] = explode('@', $controllerAction);
        $reflector = new ReflectionClass($controller);
        $parameters = $reflector->getMethod($method)->getParameters();

        $rules = [];
        foreach ($parameters as $parameter) {
            $class = $parameter->getType()?->getName();
            if ($class == \App\Http\Requests\Api\General\ListRequest::class) {
                continue;
            }
            if (is_subclass_of($class, Request::class)) {
                try {
                    $rules = (new $class)->rules();
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        return $rules;
    }

    private function getSampleValue($field): string
    {
        $samples = [
            'email' => 'admin@info.com',
            'password' => '123456',
            'phone' => '1234567890',
            'name' => 'Example Name',
            'status' => '1',
        ];

        return $samples[$field] ?? '';
    }

    private function parseDescription($rule): ?string
    {
        if ($rule instanceof Exists) {
            return 'exists';
        }

        if (is_array($rule)) {
            $final = [];
            foreach ($rule as $i) {
                if (
                    $i instanceof Exists
                    || $i instanceof Unique
                    || $i instanceof RequiredIf
                    || is_string($i)
                ) {
                    $final[] = $i;
                } elseif ($i instanceof \Closure) {
                    $final[] = $this->getClosureName($i);
                }
            }
            return join('|', $final);
        }
        return $rule;
    }

    private function getClosureName(\Closure $closure): string
    {
        $reflection = new \ReflectionFunction($closure);

        $code = file($reflection->getFileName());
        $start = $reflection->getStartLine() - 1;
        $end = $reflection->getEndLine();
        $closureCode = implode("", array_slice($code, $start, $end - $start));

        $normalizedCode = strtolower(preg_replace('/\s+/', ' ', $closureCode));

        $patterns = [
            'otp_validation_email' => ['AccountVerify::query()', 'old_email', 'otp'],
            'otp_validation_phone' => ['AccountVerify::query()', 'old_phone', 'otp'],
            'password_validation' => ['Hash::check', 'password', 'auth'],
            'unique_city_name' => ['CityTranslation::where', 'locale', 'name', 'country_id'],
            'exists_validation' => ['exists'],
            'auth_validation' => ['auth(', 'auth()->'],
            'email_validation' => ['email'],
            'phone_validation' => ['phone'],
            'different_phone' => ['auth(', 'phone', '!='],  // Detects "different phone" check
            'numeric_validation' => ['is_numeric', '->numeric'],
            'date_validation' => ['strtotime', '->date'],
            'boolean_validation' => ['true', 'false', 'boolean'],
        ];

        foreach ($patterns as $name => $keywords) {
            $matches = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($normalizedCode, strtolower($keyword))) {
                    $matches++;
                }
            }

            if ($matches === count($keywords)) {
                return $name;
            }
        }

        return '';
    }

    private function parseUrl($uri): array
    {
        $urlParts = parse_url($uri);
        $host = ['{{URL}}'];
        $path = array_filter(explode('/', $urlParts['path']));

        $query = [];
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
            foreach ($queryParams as $key => $value) {
                $query[] = [
                    'key' => $key,
                    'value' => $value,
                    'disabled' => true
                ];
            }
        }

        return [
            'raw' => '{{URL}}/' . str_replace('/api', '', $uri),
            'host' => $host,
            'path' => array_values(array_filter($path, fn($i) => $i != 'api')),
            'query' => $query
        ];
    }

    private function getRouteDescription($controllerAction): string
    {
        if (!str_contains($controllerAction, '@')) {
            return 'No description available';
        }

        [$controllerClass, $method] = explode('@', $controllerAction);

        try {
            $reflection = new ReflectionMethod($controllerClass, $method);
            $docComment = $reflection->getDocComment();

            if ($docComment) {
                $description = '';
                $lines = preg_split('/\r\n|\r|\n/', $docComment);

                foreach ($lines as $line) {
                    $line = trim($line, "/* \t");

                    if (str_starts_with($line, '@') || empty($line)) {
                        continue;
                    }

                    $description = $line;
                    break; // Get first meaningful line
                }

                return $description ?: 'No description available';
            }
        } catch (ReflectionException $e) {
            // Handle reflection exceptions quietly
        }

        return 'No description available';
    }

    private function getTestScript(): array
    {
        return [
            'listen' => 'prerequest',
            'script' => [
                'type' => 'text/javascript',
                'exec' => [
                    "pm.request.headers.add({\r",
                    "  key: \"Accept\",\r",
                    "  value: \"application/json\"\r",
                    "});\r",
                    "\r",
                    "\r",
                    "pm.request.headers.add({\r",
                    "    key: \"Accept-Language\",\r",
                    "    value: \"en\"\r",
                    "});\r",
                    "\r",
                    "pm.request.headers.add({\r",
                    "    key: \"Timezone\",\r",
                    "    value: \"Africa/Cairo\"\r",
                    "});\r",
                    ""
                ]
            ]
        ];
    }
}
