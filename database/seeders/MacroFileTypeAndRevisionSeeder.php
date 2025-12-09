<?php

namespace Database\Seeders;

use App\Models\Royalty\MacroFileRevision;
use App\Models\Royalty\MacroFileType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MacroFileTypeAndRevisionSeeder extends Seeder
{
    public function run(): void
    {
        $file_types_and_revisions = [
            [
                'Branch Fran Master',
                ['default'],
            ],
            [
                'Pro Forma National Sales by Store',
                ['default'],
            ],
            [
                'JBS Sales History By Store',
                ['default', 'updated'],
            ],
            [
                'Monthly National Sales Report',
                ['default', 'added-franchisee-data', 'added-jbmis-data', 'created-royalty-data', 'updated-royalty-data'],
            ],
            [
                'JBMIS Data',
                ['default'],
            ],
            [
                'JBMIS Code Conversion',
                ['default'],
            ],
            [
                'POS Data',
                ['default'],
            ],
            [
                'Royalty Workbook',
                ['default', 'updated'],
            ],
            [
                'Pro Forma Royalty Workbook',
                ['default'],
            ],
            [
                'JBS Sales History',
                ['default', 'updated'],
            ],
        ];

        DB::beginTransaction();

        foreach ($file_types_and_revisions as $file_type_and_revisions) {
            $macroFileType = new MacroFileType;
            $macroFileType->type = $file_type_and_revisions[0];
            $macroFileType->save();

            foreach ($file_type_and_revisions[1] as $macro_file_revision) {
                $macroFileRevision = new MacroFileRevision;
                $macroFileRevision->file_type_id = $macroFileType->id;
                $macroFileRevision->name = $macro_file_revision;
                $macroFileRevision->save();
            }
        }

        DB::commit();
    }
}
