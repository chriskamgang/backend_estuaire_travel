<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusTripResource\Pages;
use App\Filament\Resources\BusTripResource\RelationManagers;
use App\Models\BusTrip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BusTripResource extends Resource
{
    protected static ?string $model = BusTrip::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('Trajets de bus');
    }

    public static function getModelLabel(): string
    {
        return __('Trajet de bus');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Trajets de bus');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_bus_trips');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_bus_trips');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('edit_bus_trips');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_bus_trips');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_bus_trips');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('company_id')
                    ->label(__('Compagnie'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('from_city_id')
                    ->label(__('Ville de départ'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('to_city_id')
                    ->label(__("Ville d'arrivée"))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('departure_time')
                    ->label(__('Heure de départ')),
                Forms\Components\TextInput::make('arrival_time')
                    ->label(__("Heure d'arrivée")),
                Forms\Components\TextInput::make('duration')
                    ->label(__('Durée'))
                    ->maxLength(10)
                    ->default(null),
                Forms\Components\TextInput::make('price')
                    ->label(__('Prix'))
                    ->required()
                    ->numeric()
                    ->suffix('FCFA'),
                Forms\Components\TextInput::make('total_seats')
                    ->label(__('Places totales'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('bus_type')
                    ->label(__('Type de bus'))
                    ->required(),
                Forms\Components\Textarea::make('amenities')
                    ->label(__('Équipements'))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('stops')
                    ->label(__('Arrêts'))
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('recurring')
                    ->label(__('Récurrent'))
                    ->required(),
                Forms\Components\Textarea::make('recurring_days')
                    ->label(__('Jours récurrents'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->label(__('Statut'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('Compagnie'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('fromCity.name')
                    ->label(__('Ville de départ'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('toCity.name')
                    ->label(__("Ville d'arrivée"))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('departure_time')
                    ->label(__('Heure de départ')),
                Tables\Columns\TextColumn::make('arrival_time')
                    ->label(__("Heure d'arrivée")),
                Tables\Columns\TextColumn::make('duration')
                    ->label(__('Durée'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('Prix'))
                    ->money('XAF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_seats')
                    ->label(__('Places totales'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bus_type')
                    ->label(__('Type de bus')),
                Tables\Columns\IconColumn::make('recurring')
                    ->label(__('Récurrent'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Statut')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Créé le'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Mis à jour le'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusTrips::route('/'),
            'create' => Pages\CreateBusTrip::route('/create'),
            'edit' => Pages\EditBusTrip::route('/{record}/edit'),
        ];
    }
}
