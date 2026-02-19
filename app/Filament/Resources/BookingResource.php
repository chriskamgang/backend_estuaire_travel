<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('Réservations');
    }

    public static function getModelLabel(): string
    {
        return __('Réservation');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Réservations');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_bookings');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_bookings');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('edit_bookings');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_bookings');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_bookings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->label(__('Utilisateur'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('bus_trip_id')
                    ->label(__('Trajet de bus'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('booking_reference')
                    ->label(__('Référence de réservation'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('travel_date')
                    ->label(__('Date de voyage'))
                    ->required(),
                Forms\Components\Textarea::make('seats')
                    ->label(__('Sièges'))
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('number_of_seats')
                    ->label(__('Nombre de sièges'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_price')
                    ->label(__('Prix total'))
                    ->required()
                    ->numeric()
                    ->suffix('FCFA'),
                Forms\Components\TextInput::make('passenger_name')
                    ->label(__('Nom du passager'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('passenger_phone')
                    ->label(__('Téléphone du passager'))
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('passenger_email')
                    ->label(__('Email du passager'))
                    ->email()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('payment_method')
                    ->label(__('Méthode de paiement'))
                    ->required(),
                Forms\Components\TextInput::make('payment_status')
                    ->label(__('Statut du paiement'))
                    ->required(),
                Forms\Components\TextInput::make('payment_reference')
                    ->label(__('Référence de paiement'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('status')
                    ->label(__('Statut'))
                    ->required(),
                Forms\Components\Toggle::make('used_free_trip')
                    ->label(__('Voyage gratuit utilisé'))
                    ->required(),
                Forms\Components\Textarea::make('cancellation_reason')
                    ->label(__('Raison d\'annulation'))
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('cancelled_at')
                    ->label(__('Annulé le')),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Informations de réservation'))
                    ->schema([
                        Infolists\Components\TextEntry::make('booking_reference')
                            ->label(__('Référence de réservation'))
                            ->copyable()
                            ->size('lg')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('Statut'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'confirmed' => 'success',
                                'pending' => 'warning',
                                'cancelled' => 'danger',
                                'completed' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('travel_date')
                            ->label(__('Date de voyage'))
                            ->date('d F Y'),
                        Infolists\Components\TextEntry::make('number_of_seats')
                            ->label(__('Nombre de sièges')),
                        Infolists\Components\TextEntry::make('total_price')
                            ->label(__('Prix total'))
                            ->money('XAF')
                            ->size('lg')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('used_free_trip')
                            ->label(__('Voyage gratuit utilisé'))
                            ->formatStateUsing(fn ($state): string => $state ? 'Oui' : 'Non')
                            ->color(fn ($state): string => $state ? 'success' : 'gray'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('Informations du client'))
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label(__('Nom')),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label(__('Email'))
                            ->copyable(),
                        Infolists\Components\TextEntry::make('user.phone')
                            ->label(__('Téléphone'))
                            ->copyable(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('Informations du passager'))
                    ->schema([
                        Infolists\Components\TextEntry::make('passenger_name')
                            ->label(__('Nom du passager')),
                        Infolists\Components\TextEntry::make('passenger_phone')
                            ->label(__('Téléphone du passager'))
                            ->copyable(),
                        Infolists\Components\TextEntry::make('passenger_email')
                            ->label(__('Email du passager'))
                            ->copyable()
                            ->placeholder('Non renseigné'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('Détails du trajet'))
                    ->schema([
                        Infolists\Components\TextEntry::make('busTrip.company.name')
                            ->label(__('Compagnie')),
                        Infolists\Components\TextEntry::make('busTrip.fromCity.name')
                            ->label(__('Ville de départ')),
                        Infolists\Components\TextEntry::make('busTrip.toCity.name')
                            ->label(__("Ville d'arrivée")),
                        Infolists\Components\TextEntry::make('busTrip.departure_time')
                            ->label(__('Heure de départ')),
                        Infolists\Components\TextEntry::make('busTrip.arrival_time')
                            ->label(__("Heure d'arrivée")),
                        Infolists\Components\TextEntry::make('busTrip.bus_type')
                            ->label(__('Type de bus')),
                        Infolists\Components\TextEntry::make('seats')
                            ->label(__('Numéros de sièges'))
                            ->formatStateUsing(fn ($state): string =>
                                is_array($state) ? implode(', ', $state) : $state
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('Informations de paiement'))
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label(__('Méthode de paiement')),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label(__('Statut du paiement'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('payment_reference')
                            ->label(__('Référence de paiement'))
                            ->copyable()
                            ->placeholder('Non renseignée'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('Annulation'))
                    ->schema([
                        Infolists\Components\TextEntry::make('cancelled_at')
                            ->label(__('Annulé le'))
                            ->dateTime('d F Y à H:i')
                            ->placeholder('Non annulé'),
                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->label(__("Raison d'annulation"))
                            ->placeholder('Aucune')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->visible(fn ($record): bool => $record->cancelled_at !== null),

                Infolists\Components\Section::make(__('Dates'))
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('Créé le'))
                            ->dateTime('d F Y à H:i'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('Mis à jour le'))
                            ->dateTime('d F Y à H:i'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_reference')
                    ->label(__('Référence'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->description(fn ($record): string => $record->passenger_name),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Client'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->user->email ?? ''),
                Tables\Columns\TextColumn::make('busTrip')
                    ->label(__('Trajet'))
                    ->formatStateUsing(fn ($record) =>
                        ($record->busTrip->fromCity->name ?? '') . ' → ' . ($record->busTrip->toCity->name ?? '')
                    )
                    ->description(fn ($record): string =>
                        ($record->busTrip->company->name ?? '') . ' - ' . $record->busTrip->departure_time
                    ),
                Tables\Columns\TextColumn::make('travel_date')
                    ->label(__('Date de voyage'))
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_seats')
                    ->label(__('Places'))
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label(__('Prix'))
                    ->money('XAF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Statut'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
