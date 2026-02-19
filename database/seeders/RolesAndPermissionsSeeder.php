<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            // Gestion des utilisateurs du système
            'view_system_users',
            'create_system_users',
            'edit_system_users',
            'delete_system_users',

            // Gestion des clients
            'view_clients',
            'create_clients',
            'edit_clients',
            'delete_clients',

            // Gestion des chauffeurs
            'view_drivers',
            'create_drivers',
            'edit_drivers',
            'delete_drivers',

            // Gestion des trajets de bus
            'view_bus_trips',
            'create_bus_trips',
            'edit_bus_trips',
            'delete_bus_trips',

            // Gestion des réservations
            'view_bookings',
            'create_bookings',
            'edit_bookings',
            'delete_bookings',

            // Gestion des covoiturages
            'view_rideshares',
            'create_rideshares',
            'edit_rideshares',
            'delete_rideshares',

            // Gestion des compagnies
            'view_companies',
            'create_companies',
            'edit_companies',
            'delete_companies',

            // Gestion des villes
            'view_cities',
            'create_cities',
            'edit_cities',
            'delete_cities',

            // Gestion des véhicules
            'view_vehicles',
            'create_vehicles',
            'edit_vehicles',
            'delete_vehicles',

            // Gestion des paramètres
            'view_settings',
            'edit_settings',

            // Rapports et statistiques
            'view_reports',
            'export_data',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions

        // Super Admin - Accès complet
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - Accès à tout sauf les utilisateurs système
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view_clients', 'create_clients', 'edit_clients', 'delete_clients',
            'view_drivers', 'create_drivers', 'edit_drivers', 'delete_drivers',
            'view_bus_trips', 'create_bus_trips', 'edit_bus_trips', 'delete_bus_trips',
            'view_bookings', 'create_bookings', 'edit_bookings', 'delete_bookings',
            'view_rideshares', 'create_rideshares', 'edit_rideshares', 'delete_rideshares',
            'view_companies', 'create_companies', 'edit_companies', 'delete_companies',
            'view_cities', 'create_cities', 'edit_cities', 'delete_cities',
            'view_vehicles', 'create_vehicles', 'edit_vehicles', 'delete_vehicles',
            'view_settings',
            'view_reports', 'export_data',
        ]);

        // Manager - Gestion des réservations et visualisation
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view_clients', 'edit_clients',
            'view_drivers', 'edit_drivers',
            'view_bus_trips', 'edit_bus_trips',
            'view_bookings', 'create_bookings', 'edit_bookings',
            'view_rideshares', 'edit_rideshares',
            'view_companies',
            'view_cities',
            'view_vehicles',
            'view_reports',
        ]);

        // Support - Lecture seule et gestion limitée des réservations
        $support = Role::create(['name' => 'support']);
        $support->givePermissionTo([
            'view_clients',
            'view_drivers',
            'view_bus_trips',
            'view_bookings', 'edit_bookings',
            'view_rideshares',
            'view_companies',
            'view_cities',
            'view_vehicles',
        ]);

        // Assigner le rôle super_admin à l'utilisateur admin existant
        $adminUser = User::where('email', 'admin@estuairetravel.cm')->first();
        if ($adminUser) {
            $adminUser->assignRole('super_admin');
        }
    }
}
