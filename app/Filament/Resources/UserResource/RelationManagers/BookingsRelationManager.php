<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('booking_reference')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('booking_reference')
            ->columns([
                Tables\Columns\TextColumn::make('booking_reference')
                    ->label(__('Référence'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->description(fn ($record): string => $record->passenger_name),
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
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Statut'))
                    ->options([
                        'pending' => __('En attente'),
                        'confirmed' => __('Confirmé'),
                        'completed' => __('Complété'),
                        'cancelled' => __('Annulé'),
                    ]),
            ])
            ->headerActions([
                // Désactiver la création depuis ici
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('travel_date', 'desc');
    }
}
