<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RideshareTripResource\Pages;
use App\Filament\Resources\RideshareTripResource\RelationManagers;
use App\Models\RideshareTrip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RideshareTripResource extends Resource
{
    protected static ?string $model = RideshareTrip::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('Covoiturages');
    }

    public static function getModelLabel(): string
    {
        return __('Covoiturage');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Covoiturages');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_rideshares');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_rideshares');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('edit_rideshares');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_rideshares');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_rideshares');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('driver_id')
                    ->label(__('Chauffeur'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('vehicle_id')
                    ->label(__('Véhicule'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('from_city')
                    ->label(__('Ville de départ (nom)'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('to_city')
                    ->label(__('Ville d\'arrivée (nom)'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('departure_latitude')
                    ->label(__('Latitude de départ'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('departure_longitude')
                    ->label(__('Longitude de départ'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('departure_address')
                    ->label(__('Adresse de départ'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('arrival_latitude')
                    ->label(__('Latitude d\'arrivée'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('arrival_longitude')
                    ->label(__('Longitude d\'arrivée'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('arrival_address')
                    ->label(__('Adresse d\'arrivée'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('departure_point')
                    ->label(__('Point de départ'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('arrival_point')
                    ->label(__('Point d\'arrivée'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DatePicker::make('date')
                    ->label(__('Date'))
                    ->required(),
                Forms\Components\TextInput::make('departure_time')
                    ->label(__('Heure de départ'))
                    ->required(),
                Forms\Components\TextInput::make('arrival_time')
                    ->label(__('Heure d\'arrivée'))
                    ->required(),
                Forms\Components\TextInput::make('duration')
                    ->label(__('Durée'))
                    ->required()
                    ->maxLength(10),
                Forms\Components\TextInput::make('price_per_seat')
                    ->label(__('Prix par siège'))
                    ->required()
                    ->numeric()
                    ->suffix('FCFA'),
                Forms\Components\TextInput::make('total_seats')
                    ->label(__('Places totales'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('available_seats')
                    ->label(__('Places disponibles'))
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('stops')
                    ->label(__('Arrêts'))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('preferences')
                    ->label(__('Préférences'))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('additional_notes')
                    ->label(__('Notes additionnelles'))
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('instant')
                    ->label(__('Instantané'))
                    ->required(),
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
                Tables\Columns\TextColumn::make('driver_id')
                    ->label(__('Chauffeur'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle_id')
                    ->label(__('Véhicule'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('from_city')
                    ->label(__('Ville de départ (nom)'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('to_city')
                    ->label(__('Ville d\'arrivée (nom)'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('departure_latitude')
                    ->label(__('Latitude de départ'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('departure_longitude')
                    ->label(__('Longitude de départ'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('departure_address')
                    ->label(__('Adresse de départ'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('arrival_latitude')
                    ->label(__('Latitude d\'arrivée'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('arrival_longitude')
                    ->label(__('Longitude d\'arrivée'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('arrival_address')
                    ->label(__('Adresse d\'arrivée'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('departure_point')
                    ->label(__('Point de départ'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('arrival_point')
                    ->label(__('Point d\'arrivée'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('Date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('departure_time')
                    ->label(__('Heure de départ')),
                Tables\Columns\TextColumn::make('arrival_time')
                    ->label(__('Heure d\'arrivée')),
                Tables\Columns\TextColumn::make('duration')
                    ->label(__('Durée'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_per_seat')
                    ->label(__('Prix par siège'))
                    ->money('XAF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_seats')
                    ->label(__('Places totales'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('available_seats')
                    ->label(__('Places disponibles'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('instant')
                    ->label(__('Instantané'))
                    ->boolean(),
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
            'index' => Pages\ListRideshareTrips::route('/'),
            'create' => Pages\CreateRideshareTrip::route('/create'),
            'edit' => Pages\EditRideshareTrip::route('/{record}/edit'),
        ];
    }
}
