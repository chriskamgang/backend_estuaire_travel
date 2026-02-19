<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Models\WalletTransaction;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon        = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup       = 'Finances';
    protected static ?int    $navigationSort         = 1;
    protected static ?string $navigationLabel        = null;
    protected static ?string $modelLabel             = null;
    protected static ?string $pluralModelLabel       = null;

    public static function getNavigationLabel(): string
    {
        return 'Transactions';
    }

    public static function getModelLabel(): string
    {
        return 'Transaction';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Transactions wallet';
    }

    // Lecture seule — pas de création/édition via admin
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Détails de la transaction')
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')
                        ->label('Utilisateur'),
                    Infolists\Components\TextEntry::make('user.phone')
                        ->label('Téléphone')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('type')
                        ->label('Type')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'recharge'     => 'success',
                            'debit'        => 'danger',
                            'transfer'     => 'warning',
                            'refund'       => 'info',
                            'subscription' => 'primary',
                            default        => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'recharge'     => 'Rechargement',
                            'debit'        => 'Débit',
                            'transfer'     => 'Transfert',
                            'refund'       => 'Remboursement',
                            'subscription' => 'Abonnement',
                            default        => $state,
                        }),
                    Infolists\Components\TextEntry::make('payment_status')
                        ->label('Statut paiement')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'success' => 'success',
                            'pending' => 'warning',
                            'failed'  => 'danger',
                            default   => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('amount')
                        ->label('Montant')
                        ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                        ->size('lg')
                        ->weight('bold'),
                    Infolists\Components\TextEntry::make('balance_before')
                        ->label('Solde avant')
                        ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA'),
                    Infolists\Components\TextEntry::make('balance_after')
                        ->label('Solde après')
                        ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA'),
                    Infolists\Components\TextEntry::make('label')
                        ->label('Libellé')
                        ->placeholder('—'),
                ])
                ->columns(2),

            Infolists\Components\Section::make('Informations de paiement')
                ->schema([
                    Infolists\Components\TextEntry::make('payment_method')
                        ->label('Méthode')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('freemopay_reference')
                        ->label('Réf. Freemopay')
                        ->copyable()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('external_id')
                        ->label('ID externe')
                        ->copyable()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('transfer_to_phone')
                        ->label('Transfert vers')
                        ->placeholder('—'),
                ])
                ->columns(2),

            Infolists\Components\Section::make('Dates')
                ->schema([
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Date')
                        ->dateTime('d F Y à H:i'),
                ])
                ->columns(1)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->description(fn ($record): string => $record->user?->phone ?? ''),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'recharge'     => 'success',
                        'debit'        => 'danger',
                        'transfer'     => 'warning',
                        'refund'       => 'info',
                        'subscription' => 'primary',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'recharge'     => 'Rechargement',
                        'debit'        => 'Débit',
                        'transfer'     => 'Transfert',
                        'refund'       => 'Remboursement',
                        'subscription' => 'Abonnement',
                        default        => $state,
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->sortable()
                    ->color(fn ($record): string => in_array($record->type, ['recharge', 'refund']) ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Solde après')
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')
                    ->limit(40)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'pending' => 'warning',
                        'failed'  => 'danger',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Méthode')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'recharge'     => 'Rechargement',
                        'debit'        => 'Débit',
                        'transfer'     => 'Transfert',
                        'refund'       => 'Remboursement',
                        'subscription' => 'Abonnement',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Statut')
                    ->options([
                        'success' => 'Succès',
                        'pending' => 'En attente',
                        'failed'  => 'Échoué',
                    ]),

                Tables\Filters\Filter::make('ce_mois')
                    ->label('Ce mois')
                    ->query(fn (Builder $query) => $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)),

                Tables\Filters\Filter::make('mois_dernier')
                    ->label('Mois dernier')
                    ->query(fn (Builder $query) => $query->whereMonth('created_at', now()->subMonth()->month)
                        ->whereYear('created_at', now()->subMonth()->year)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Exporter CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $transactions = WalletTransaction::with('user')
                            ->orderBy('created_at', 'desc')
                            ->get();

                        $csv  = "Date,Utilisateur,Téléphone,Type,Montant,Solde avant,Solde après,Libellé,Statut,Méthode\n";
                        foreach ($transactions as $t) {
                            $csv .= implode(',', [
                                '"' . $t->created_at->format('d/m/Y H:i') . '"',
                                '"' . ($t->user?->name ?? '') . '"',
                                '"' . ($t->user?->phone ?? '') . '"',
                                '"' . $t->type . '"',
                                number_format($t->amount, 2, '.', ''),
                                number_format($t->balance_before, 2, '.', ''),
                                number_format($t->balance_after, 2, '.', ''),
                                '"' . str_replace('"', '""', $t->label ?? '') . '"',
                                '"' . $t->payment_status . '"',
                                '"' . ($t->payment_method ?? '') . '"',
                            ]) . "\n";
                        }

                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, 'transactions_' . now()->format('Y-m-d') . '.csv', [
                            'Content-Type' => 'text/csv',
                        ]);
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'view'  => Pages\ViewWalletTransaction::route('/{record}'),
        ];
    }
}
