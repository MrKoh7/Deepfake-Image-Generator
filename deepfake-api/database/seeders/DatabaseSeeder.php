<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\ModelEntry;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
   public function run()
{
    // ✅ Only create admin if not exists
    if (!User::where('email', 'admin@example.com')->exists()) {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);
    }

    // ✅ Seed models only if table empty
    if (ModelEntry::count() === 0) {
        $models = [
            ['name' => 'African Male',     'path' => 'python/GANmodels/generator_africa_male_final(2K).h5'],
            ['name' => 'Asian Female',     'path' => 'python/GANmodels/generator_asian_female_final2K.h5'],
            ['name' => 'Asian Male',       'path' => 'python/GANmodels/generator_asian_male_final(2K).h5'],
            ['name' => 'European Female',  'path' => 'python/GANmodels/generator_european_female_final(2K).h5'],
            ['name' => 'European Male',    'path' => 'python/GANmodels/generator_european_male_final(2k).h5'],
        ];

        foreach ($models as $m) {
            ModelEntry::create($m);
        }
    }
}

}
