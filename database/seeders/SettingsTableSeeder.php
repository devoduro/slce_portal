<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // System settings
        $systemSettings = [
            [
                'key' => 'institution_name',
                'category' => 'system',
                'value' => 'ST. LOUIS COLLEGE OF EDUCATION',
                'description' => 'Name of the institution',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'institution_code',
                'category' => 'system',
                'value' => 'SLCE',
                'description' => 'Institution code',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'institution_address',
                'category' => 'system',
                'value' => 'P. O. Box 3041. Mbrom, Kumasi',
                'description' => 'Institution address',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'institution_contact',
                'category' => 'system',
                'value' => '+233 33 200 8651',
                'description' => 'Institution contact number',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'transcript_prefix',
                'category' => 'system',
                'value' => 'TR',
                'description' => 'Prefix for transcript numbers',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'transcript_footer',
                'category' => 'system',
                'value' => 'This transcript is not valid without the university seal and signature.',
                'description' => 'Text to appear at the bottom of transcripts',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'transcript_signature',
                'category' => 'system',
                'value' => 'Registrar',
                'description' => 'Title of the signing authority',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'email_from_address',
                'category' => 'system',
                'value' => ' info@slce.edu.gh',
                'description' => 'Email address used for sending system emails',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'email_from_name',
                'category' => 'system',
                'value' => 'ST. LOUIS COLLEGE OF EDUCATION',
                'description' => 'Name used for sending system emails',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        
        // Institution settings
        $institutionSettings = [
            [
                'key' => 'institution_name',
                'category' => 'institution',
                'value' => 'ST. LOUIS COLLEGE OF EDUCATION',
                'description' => 'Name of the institution',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'institution_slogan',
                'category' => 'institution',
                'value' => 'Excellence in Education',
                'description' => 'Institution slogan or motto',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'institution_address',
                'category' => 'institution',
                'value' => 'P.O. Box 3041, Mbrom, Kumasi',
                'description' => 'Institution address',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'institution_phone',
                'category' => 'institution',
                'value' => '+233 33 200 8651',
                'description' => 'Institution phone number',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'institution_email',
                'category' => 'institution',
                'value' => ' info@slce.edu.gh',
                'description' => 'Institution email address',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'institution_website',
                'category' => 'institution',
                'value' => 'https://www.slce.edu.gh',
                'description' => 'Institution website URL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        
        // Insert all settings
        DB::table('settings')->insert(array_merge($systemSettings, $institutionSettings));
    }
}
