<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Génère un dump SQL complet de la base ({{ config('database.connections.'.config('database.default').'.database') }})
        et le télécharge. Contient toutes les tables, y compris les comptes utilisateurs.
    </p>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        La restauration écrase toute la base avec le dump importé. Ton compte admin
        ({{ auth()->user()?->email }}) est réinjecté après l'import : pas de risque de te
        verrouiller dehors.
    </p>
</x-filament-panels::page>
