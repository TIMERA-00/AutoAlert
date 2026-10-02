<?php

namespace Database\Seeders;

use App\Enums\AlertFrequency;
use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\SourceType;
use App\Enums\Transmission;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\Alert;
use App\Models\Source;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Initialisation de la base AutoAlert...');

        // ------------------------------------------------------------- admin
        $admin = User::updateOrCreate(
            ['email' => env('ADMIN_SEED_EMAIL', 'admin@autoalert.dev')],
            [
                'first_name' => 'Admin',
                'last_name' => 'AutoAlert',
                'phone' => '+221 770000001',
                'password' => Hash::make(env('ADMIN_SEED_PASSWORD', 'Admin@2024')),
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $this->command?->info("  Admin : {$admin->email}");

        // ----------------------------------------------------------- sources
        $sources = collect([
            ['Saisie interne', 'https://autoalert.dev/saisie', SourceType::Manual, true],
            ['Auto Senegal', 'https://autosenegal.example.com', SourceType::Scraper, true],
            ['Place Auto', 'https://placeauto.example.com', SourceType::Partner, true],
            ['Import manuel', 'https://import.example.com', SourceType::Manual, false],
        ])->map(fn (array $row, int $index) => Source::updateOrCreate(
            ['name' => $row[0]],
            ['base_url' => $row[1], 'type' => $row[2], 'is_active' => $row[3], 'notes' => $index === 0 ? 'Annonces saisies directement par l equipe.' : null]
        ));

        if (! env('SEED_DEMO_DATA', true)) {
            $this->command?->info('  Donnees de demonstration desactivees (SEED_DEMO_DATA=false).');

            return;
        }

        // --------------------------------------------------------- vehicules
        $catalog = [
            ['Toyota', 'RAV4', 2023, 19_500_000, 47_000, FuelType::Hybrid, Transmission::Automatic, BodyType::Suv, 'Dakar', 'Occasion recente, controle technique a jour, siege en cuir.', VehicleStatus::Published],
            ['Hyundai', 'Tucson', 2022, 18_000_000, 62_000, FuelType::Diesel, Transmission::Automatic, BodyType::Suv, 'Dakar', 'SUV familial,amera 360, siege chauffant.', VehicleStatus::Published],
            ['Peugeot', '3008', 2021, 16_800_000, 88_000, FuelType::Diesel, Transmission::Automatic, BodyType::Suv, 'Thies', 'Bon etat general,historique d entretien complet.', VehicleStatus::Published],
            ['Renault', 'Clio', 2020, 8_500_000, 74_000, FuelType::Petrol, Transmission::Manual, BodyType::Hatchback, 'Dakar', 'Clio 5, ideal pour la ville.', VehicleStatus::Published],
            ['Renault', 'Duster', 2019, 12_400_000, 120_000, FuelType::Diesel, Transmission::Manual, BodyType::Suv, 'Saint-Louis', '4x4 robuste, pneus neufs.', VehicleStatus::Published],
            ['Mercedes', 'Classe C', 2021, 31_000_000, 55_000, FuelType::Petrol, Transmission::Automatic, BodyType::Sedan, 'Dakar', 'Full options, garantie concession.', VehicleStatus::Published],
            ['Toyota', 'Hilux', 2022, 24_500_000, 55_000, FuelType::Diesel, Transmission::Automatic, BodyType::Pickup, 'Kaolack', 'Benne_places, usage professionnel.', VehicleStatus::Published],
            ['Nissan', 'Qashqai', 2020, 15_000_000, 95_000, FuelType::Diesel, Transmission::Automatic, BodyType::Suv, 'Ziguinchor', 'Confort de conduite, bon rapport quality/prix.', VehicleStatus::Published],
            ['BMW', 'Serie 1', 2019, 14_200_000, 96_000, FuelType::Petrol, Transmission::Automatic, BodyType::Hatchback, 'Dakar', 'Sportive, entretien recent.', VehicleStatus::Reserved],
            ['Mitsubishi', 'Lancer', 2018, 9_800_000, 145_000, FuelType::Petrol, Transmission::Manual, BodyType::Sedan, 'Touba', 'Berline fiable, freins neufs.', VehicleStatus::Published],
            ['Hyundai', 'i10', 2023, 7_900_000, 18_000, FuelType::Petrol, Transmission::Manual, BodyType::Hatchback, 'Dakar', 'Ville, quasi neuf, garantie 2 ans.', VehicleStatus::Published],
            ['Toyota', 'Corolla', 2017, 10_500_000, 132_000, FuelType::Hybrid, Transmission::Automatic, BodyType::Sedan, 'Thies', 'Hybride, consommation 4L/100km.', VehicleStatus::Sold],
            ['Kia', 'Sportage', 2024, 27_000_000, 12_000, FuelType::Petrol, Transmission::Automatic, BodyType::Suv, 'Dakar', 'Neuf, garantie constructeur.', VehicleStatus::Draft],
            ['Dacia', 'Duster', 2016, 6_800_000, 168_000, FuelType::Diesel, Transmission::Manual, BodyType::Suv, 'Louga', 'Entretien soigne, ideal budget.', VehicleStatus::Archived],
        ];

        foreach ($catalog as $index => [$brand, $model, $year, $price, $mileage, $fuel, $transmission, $body, $location, $description, $status]) {
            $vehicle = Vehicle::updateOrCreate(
                ['brand' => $brand, 'model' => $model, 'year' => $year],
                [
                    'source_id' => $index === 0 ? $sources[0]->id : $sources[1]->id,
                    'source_url' => $index === 0 ? null : 'https://autosenegal.example.com/annonce/'.(1000 + $index),
                    'price' => $price,
                    'mileage' => $mileage,
                    'fuel' => $fuel,
                    'transmission' => $transmission,
                    'body_type' => $body,
                    'color' => ['Blanc', 'Noir', 'Argent', 'Gris'][array_rand(['Blanc', 'Noir', 'Argent', 'Gris'])],
                    'location' => $location,
                    'description' => $description,
                    'status' => $status,
                    'published_at' => $status === VehicleStatus::Draft ? null : now()->subDays($index * 3),
                    'reference' => str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                    'view_count' => random_int(3, 240),
                ]
            );

            $vehicle->images()->delete();
            for ($i = 0; $i < 3; $i++) {
                $vehicle->images()->create([
                    'url' => sprintf(
                        'https://images.unsplash.com/photo-%s?auto=format&fit=crop&w=1200&q=70',
                        ['1552519507-da3b142c6e3d', '1503376780353-7e6692767b70', '1541899481282-d53bffe3c35d', '1494976388531-d1058494cdd8', '1550355291-bbee04a92027'][$i]
                    ),
                    'provider' => 'external',
                    'sort_order' => $i,
                    'is_primary' => $i === 0,
                    'alt' => "{$brand} {$model}",
                ]);
            }
        }

        $this->command?->info('  '.Vehicle::count().' vehicules.');

        // --------------------------------------------------------- utilisateurs
        $mamadou = User::updateOrCreate(
            ['email' => 'mamadou@example.com'],
            [
                'first_name' => 'Mamadou',
                'last_name' => 'Diop',
                'phone' => '+221 771234567',
                'password' => Hash::make('Password@2024'),
                'role' => UserRole::User,
                'is_active' => true,
                'email_verified_at' => now(),
                'notify_whatsapp' => true,
            ]
        );

        foreach (range(1, 6) as $i) {
            User::factory()->create(['email' => "client{$i}@example.com"]);
        }

        $this->command?->info('  '.User::count().' utilisateurs.');

        // ------------------------------------------------------------- alertes
        Alert::query()->delete();

        Alert::create([
            'user_id' => $mamadou->id,
            'name' => 'Toyota RAV4 automatique',
            'brand' => 'Toyota',
            'model' => 'RAV4',
            'min_year' => 2021,
            'max_price' => 20_000_000,
            'max_mileage' => 100_000,
            'fuel' => FuelType::Hybrid,
            'transmission' => Transmission::Automatic,
            'frequency' => AlertFrequency::Immediate,
        ]);

        Alert::create([
            'user_id' => $mamadou->id,
            'name' => 'SUV automatique moins de 25M',
            'max_price' => 25_000_000,
            'transmission' => Transmission::Automatic,
            'body_type' => BodyType::Suv,
            'frequency' => AlertFrequency::Daily,
        ]);

        Alert::create([
            'user_id' => $mamadou->id,
            'name' => 'Berline diesel',
            'fuel' => FuelType::Diesel,
            'max_price' => 18_000_000,
            'frequency' => AlertFrequency::Weekly,
        ]);

        $this->command?->info('  '.Alert::count().' alertes.');

        // ---------------------------------------------------------- favoris
        Vehicle::where('brand', 'Toyota')->limit(1)->get()
            ->concat(Vehicle::where('brand', 'Hyundai')->limit(1)->get())
            ->each(fn (Vehicle $vehicle) => $mamadou->favorites()->create(['vehicle_id' => $vehicle->id]));

        Vehicle::where('brand', 'Toyota')->limit(1)->get()->each(
            fn (Vehicle $vehicle) => $vehicle->increment('favorite_count')
        );

        $this->command?->info('  Base initialisee.');
        $this->command?->newLine();
        $this->command?->info('  Comptes de demonstration :');
        $this->command?->table(
            ['Role', 'Email', 'Mot de passe'],
            [
                ['Admin', $admin->email, env('ADMIN_SEED_PASSWORD', 'Admin@2024')],
                ['Utilisateur', 'mamadou@example.com', 'Password@2024'],
            ]
        );
    }
}
