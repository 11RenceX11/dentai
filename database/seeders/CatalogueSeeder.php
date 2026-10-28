<?php

namespace Database\Seeders;

use App\Models\ProblemType;
use App\Models\Treatment;
use Illuminate\Database\Seeder;

/** Treatments + the booking drop-down. Safe to run in production. */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Treatment catalogue ----
        $catalogue = [
            ['Check-up & Cleaning', 'Routine exam with scale and polish', 60, 30],
            ['Emergency Examination', 'Focused exam for pain or trauma', 80, 30],
            ['Dental X-Ray', 'Diagnostic radiograph', 40, 15],
            ['Filling', 'Composite filling for a cavity', 120, 45],
            ['Root Canal Therapy', 'Treatment of infected or inflamed pulp', 650, 90],
            ['Tooth Extraction', 'Removal of a damaged or impacted tooth', 180, 45],
            ['Crown', 'Cap to restore a damaged tooth', 800, 60],
            ['Gum Treatment', 'Deep cleaning and gum care', 150, 45],
            ['Teeth Whitening', 'In-clinic whitening', 300, 60],
            ['Orthodontic Consultation', 'Braces / aligner assessment', 70, 30],
        ];
        foreach ($catalogue as [$name, $desc, $cost, $mins]) {
            Treatment::create(['treatment_name' => $name, 'description' => $desc, 'base_cost' => $cost, 'est_duration_minutes' => $mins]);
        }
        $t = fn (string $n) => Treatment::where('treatment_name', $n)->value('id');

        // ---- Problem types for the booking drop-down ----
        $problems = [
            ['Toothache', 'Pain in or around a tooth', 'normal', 'Emergency Examination'],
            ['Tooth sensitivity', 'Pain with hot, cold or sweet', 'low', 'Check-up & Cleaning'],
            ['Bleeding or sore gums', 'Gums bleed when brushing or feel sore', 'normal', 'Gum Treatment'],
            ['Swelling in face or gums', 'Swelling, possible infection', 'high', 'Emergency Examination'],
            ['Chipped or broken tooth', 'Tooth cracked, chipped or broken', 'high', 'Emergency Examination'],
            ['Knocked-out tooth', 'Tooth lost through an injury', 'emergency', 'Emergency Examination'],
            ['Cavity or hole in tooth', 'Visible hole or dark spot', 'normal', 'Filling'],
            ['Lost filling or crown', 'A filling or crown has come out', 'normal', 'Crown'],
            ['Wisdom tooth problem', 'Pain or pressure at the back of the mouth', 'normal', 'Tooth Extraction'],
            ['Bad breath', 'Persistent bad breath', 'low', 'Check-up & Cleaning'],
            ['Cleaning / check-up', 'Routine visit', 'low', 'Check-up & Cleaning'],
            ['Braces or alignment', 'Crooked teeth or bite concerns', 'low', 'Orthodontic Consultation'],
            ['Other', 'Something else — describe it below', 'normal', 'Emergency Examination'],
        ];
        foreach ($problems as [$name, $desc, $urgency, $treatment]) {
            ProblemType::create(['name' => $name, 'description' => $desc, 'default_urgency' => $urgency, 'suggested_treatment_id' => $t($treatment)]);
        }
    }
}
