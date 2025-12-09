<?php

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filters\Filter;

class FuzzyFilter implements Filter
{
    /** @var string[] */
    protected array $fields;

    public function __construct(string ...$fields)
    {
        $this->fields = $fields;
    }

    public function __invoke(Builder $builder, $values, string $property): Builder
    {
        $values = (array) $values;
        $driver = DB::getDriverName();

        $builder->where(function (Builder $builder) use ($values, $driver): void {
            foreach ($values as $input) {
                // Split into multiple words
                $keywords = preg_split('/\s+/', trim($input));

                foreach ($keywords as $word) {
                    $builder->where(function (Builder $sub) use ($word, $driver) {
                        foreach ($this->fields as $field) {
                            if ($field === 'franchisee_name') {
                                $sub->orWhereHas('franchisee', function ($q) use ($word, $driver) {
                                    $rawConcat = $driver === 'sqlsrv'
                                        ? "first_name + ' ' + middle_name + ' ' + last_name"
                                        : "CONCAT_WS(' ', first_name, middle_name, last_name)";

                                    $q->whereRaw("{$rawConcat} LIKE ?", ["%{$word}%"]);
                                });
                            } elseif ($field === 'document_franchisee_name') {
                                $sub->orWhereHas('documentable', function ($q) use ($word, $driver) {
                                    $rawConcat = $driver === 'sqlsrv'
                                        ? "first_name + ' ' + middle_name + ' ' + last_name"
                                        : "CONCAT_WS(' ', first_name, middle_name, last_name)";

                                    $q->whereRaw("{$rawConcat} LIKE ?", ["%{$word}%"]);
                                });
                            } else {
                                $sub->orWhere($field, 'LIKE', "%{$word}%");
                            }
                        }
                    });
                }
            }
        });

        return $builder;
    }
}
