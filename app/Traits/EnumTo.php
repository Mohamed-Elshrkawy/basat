<?php

namespace App\Traits;


use Illuminate\Support\Arr;

trait EnumTo
{

    /**
     * @param string $column
     * @return \Illuminate\Support\Collection
     */
    public static function toCollect(string $column = 'value'): \Illuminate\Support\Collection
    {
        return collect(array_column(self::cases(), $column));
    }


    public static function only($keys)
    {
        return self::toCollect()->only($keys);
    }

    /**
     *
     * @param string $value
     * @return mixed
     * @throws \Exception
     */
    public static function find(string $value): mixed
    {
        if (!($index = array_search($value, self::toArray()))) {
            throw new \Exception("This $value key doest exists");
        }
        return self::cases()[$index];
    }

    public static function join($separator = ','): string
    {
        return join($separator, self::toArray());
    }

    /**
     *
     * @return array
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function values(): array
    {
        return array_map(function ($item) {
            return $item->value;
        }, self::cases());
    }

    public function except(array|self|string $keys = []): array
    {
        if ($keys instanceof self) {
            $keys = (array)$keys->value;
        } elseif (is_array($keys)) {
            $keys = array_map(
                function ($i) {
                    if ($i instanceof self) {
                        return $i->value;
                    }
                    return $i;
                },
                $keys
            );
        } else {
            $keys = (array)$keys;
        }

        return Arr::except(
            self::toArray(),
            $keys
        );
    }
}
