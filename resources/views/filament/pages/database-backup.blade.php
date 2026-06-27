<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Génère un dump SQL complet de la base ({{ config('database.connections.'.config('database.default').'.database') }})
        et le télécharge. Contient toutes les tables, y compris les comptes utilisateurs.
    </p>
</x-filament-panels::page>
