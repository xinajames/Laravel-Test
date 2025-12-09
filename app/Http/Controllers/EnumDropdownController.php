<?php

namespace App\Http\Controllers;

use App\Enums\FranchiseeStatusEnum;
use App\Enums\SalesTypeEnum;
use App\Enums\StoreGroupEnum;
use App\Enums\StoreInsuranceTypeEnum;
use App\Enums\StoreStatusEnum;
use App\Enums\StoreTypeEnum;
use App\Enums\StoreWarehouseEnum;
use App\Enums\UserStatusEnum;

class EnumDropdownController extends Controller
{
    public function getDataList($key, $alphabetical = false)
    {
        $dataMap = [
            // enums
            'franchisee-status-enum' => FranchiseeStatusEnum::class,
            'store-type-enum' => StoreTypeEnum::class,
            'store-warehouse-enum' => StoreWarehouseEnum::class,
            'store-insurance-type-enum' => StoreInsuranceTypeEnum::class,
            'store-group-enum' => StoreGroupEnum::class,
            'store-status-enum' => StoreStatusEnum::class,
            'user-status-enum' => UserStatusEnum::class,
            'sales-type-enum' => SalesTypeEnum::class,

            // dropdowns
            'background-dropdown' => config('dropdown.background'),
            'gender-dropdown' => config('dropdown.gender'),
            'generation-dropdown' => config('dropdown.generation'),
            'marital-status-dropdown' => config('dropdown.marital_status'),
            'nationality-dropdown' => config('dropdown.nationality'),
            'point-person-dropdown' => config('dropdown.point_person'),
            'religion-dropdown' => config('dropdown.religion'),
            'source-of-information-dropdown' => config('dropdown.source_of_information'),
            'locations-dropdown' => config('locations'),
            'region-dropdown' => config('dropdown.region'),
        ];

        // Validate if the given key exists in the map
        if (! isset($dataMap[$key])) {
            return response()->json(['error' => 'Invalid data type'], 400);
        }

        if (str_ends_with($key, '-enum')) {
            // Handle Enums
            $enumClass = $dataMap[$key];

            $values = [];
            foreach ($enumClass::cases() as $case) {
                $values[] = [
                    'value' => $case->value,
                    'label' => $enumClass::getDescription($case->value),
                ];
            }
        } elseif (str_ends_with($key, '-dropdown')) {
            // Handle Dropdowns
            $dropdownValues = $dataMap[$key];

            if (! is_array($dropdownValues)) {
                return response()->json(['error' => 'Invalid dropdown configuration'], 500);
            }

            $values = [];
            foreach ($dropdownValues as $option) {
                $values[] = [
                    'value' => $option,
                    'label' => $option,
                ];
            }
        } else {
            return response()->json(['error' => 'Invalid key type'], 400);
        }

        // Sort alphabetically by value if required
        if ($alphabetical) {
            array_multisort(array_column($values, 'value'), SORT_STRING, $values);
        }

        return response()->json($values);
    }
}
