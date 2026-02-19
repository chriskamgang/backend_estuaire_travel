<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverApplicationResource\Pages;
use App\Models\DriverApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DriverApplicationResource extends Resource
{
    protected static ?string $model           = DriverApplication::class;
    protected static ?string $navigationIcon  = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Demandes Chauffeurs';
    protected static ?string $modelLabel      = 'Demande Chauffeur';
    protected static ?string $pluralModelLabel = 'Demandes Chauffeurs';
    protected static ?string $navigationGroup = 'Gestion Utilisateurs';
    protected static ?int    $navigationSort   = 3;

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    // ── Badge "pending" count ─────────────────────────────────
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    // ── Form (vide — lecture seule) ───────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    // ── Infolist (vue détaillée) ──────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Infolists\Components\Section::make('Informations du chauffeur')
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')
                        ->label('Nom'),
                    Infolists\Components\TextEntry::make('user.phone')
                        ->label('Téléphone')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('user.email')
                        ->label('Email')
                        ->placeholder('—')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending'  => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default    => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'pending'  => 'En attente',
                            'approved' => 'Approuvée',
                            'rejected' => 'Rejetée',
                            default    => $state,
                        }),
                    Infolists\Components\TextEntry::make('submitted_at')
                        ->label('Soumise le')
                        ->dateTime('d F Y à H:i'),
                    Infolists\Components\TextEntry::make('reviewed_at')
                        ->label('Examinée le')
                        ->dateTime('d F Y à H:i')
                        ->placeholder('Pas encore examinée'),
                ])
                ->columns(2),

            Infolists\Components\Section::make('Permis de conduire')
                ->schema([
                    Infolists\Components\TextEntry::make('license_number')
                        ->label('Numéro de permis')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('license_expiry_date')
                        ->label('Expiration')
                        ->date('d M Y')
                        ->placeholder('—'),
                ])
                ->columns(2),

            Infolists\Components\Section::make('Documents soumis')
                ->schema([
                    Infolists\Components\ImageEntry::make('id_card_front')
                        ->label("CNI recto")
                        ->disk('public')
                        ->height(200),
                    Infolists\Components\ImageEntry::make('id_card_back')
                        ->label("CNI verso")
                        ->disk('public')
                        ->height(200),
                    Infolists\Components\ImageEntry::make('driver_license_front')
                        ->label("Permis recto")
                        ->disk('public')
                        ->height(200),
                    Infolists\Components\ImageEntry::make('driver_license_back')
                        ->label("Permis verso")
                        ->disk('public')
                        ->height(200),
                    Infolists\Components\ImageEntry::make('vehicle_photo')
                        ->label("Photo du véhicule")
                        ->disk('public')
                        ->height(200)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Infolists\Components\Section::make('Raison du rejet')
                ->schema([
                    Infolists\Components\TextEntry::make('rejection_reason')
                        ->label('Raison')
                        ->placeholder('Aucune')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record): bool => $record->status === 'rejected')
                ->columns(1),

            Infolists\Components\Section::make('Informations supplémentaires')
                ->schema([
                    Infolists\Components\TextEntry::make('additional_info')
                        ->label('Notes du chauffeur')
                        ->placeholder('Aucune')
                        ->columnSpanFull(),
                ])
                ->collapsed(),
        ]);
    }

    // ── Table (liste) ─────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Chauffeur')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->user?->phone ?? ''),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'  => 'En attente',
                        'approved' => 'Approuvée',
                        'rejected' => 'Rejetée',
                        default    => $state,
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('has_all_documents')
                    ->label('Documents complets')
                    ->getStateUsing(fn ($record): bool => $record->hasAllDocuments())
                    ->boolean(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Soumise le')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Examinée le')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'  => 'En attente',
                        'approved' => 'Approuvées',
                        'rejected' => 'Rejetées',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),

                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Approuver la demande')
                    ->modalDescription('Êtes-vous sûr de vouloir approuver cette demande ? Le chauffeur pourra publier des trajets.')
                    ->modalSubmitActionLabel('Oui, approuver')
                    ->action(function ($record) {
                        $record->approve(Auth::id());
                        // Mettre aussi à jour driver_status sur l'user
                        $record->user->update(['driver_status' => 'approved', 'is_verified' => true, 'phone_verified' => true]);
                        Notification::make()
                            ->title('Demande approuvée')
                            ->body("Le chauffeur {$record->user->name} a été approuvé.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Raison du rejet')
                            ->required()
                            ->minLength(10)
                            ->placeholder('Expliquez pourquoi la demande est rejetée...'),
                    ])
                    ->modalHeading('Rejeter la demande')
                    ->modalSubmitActionLabel('Rejeter')
                    ->action(function ($record, array $data) {
                        $record->reject(Auth::id(), $data['rejection_reason']);
                        $record->user->update(['driver_status' => 'rejected']);
                        Notification::make()
                            ->title('Demande rejetée')
                            ->body("La demande de {$record->user->name} a été rejetée.")
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve_selected')
                    ->label('Approuver la sélection')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->status === 'pending') {
                                $record->approve(Auth::id());
                                $record->user->update(['driver_status' => 'approved', 'is_verified' => true, 'phone_verified' => true]);
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title("{$count} demande(s) approuvée(s)")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverApplications::route('/'),
            'view'  => Pages\ViewDriverApplication::route('/{record}'),
        ];
    }
}
