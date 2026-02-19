<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Filament\Resources\SettingResource\RelationManagers;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static ?int $navigationSort = 99;

    public static function getNavigationLabel(): string
    {
        return __('Paramètres');
    }

    public static function getModelLabel(): string
    {
        return __('Paramètre');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Paramètres');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_settings');
    }

    public static function canCreate(): bool
    {
        return false; // Settings are predefined, no creation needed
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('edit_settings');
    }

    public static function canDelete($record): bool
    {
        return false; // Settings should not be deleted
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Informations générales'))
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label(__('Clé'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->disabled(fn ($record) => $record !== null),
                        Forms\Components\Select::make('type')
                            ->label(__('Type'))
                            ->required()
                            ->options([
                                'string' => 'String',
                                'integer' => 'Integer',
                                'boolean' => 'Boolean',
                                'json' => 'JSON',
                            ])
                            ->default('string'),
                        Forms\Components\Select::make('group')
                            ->label(__('Groupe'))
                            ->required()
                            ->options([
                                'general'      => __('Général'),
                                'loyalty'      => __('Système de fidélité'),
                                'payment'      => __('Paiement'),
                                'notification' => __('Notifications'),
                                'whatsapp'     => __('WhatsApp'),
                            ])
                            ->default('general'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('Valeur'))
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label(__('Valeur'))
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label(__('Description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label(__('Clé'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label(__('Valeur'))
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label(__('Type'))
                    ->colors([
                        'primary' => 'string',
                        'success' => 'integer',
                        'warning' => 'boolean',
                        'danger' => 'json',
                    ]),
                Tables\Columns\BadgeColumn::make('group')
                    ->label(__('Groupe'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general'      => __('Général'),
                        'loyalty'      => __('Système de fidélité'),
                        'payment'      => __('Paiement'),
                        'notification' => __('Notifications'),
                        'whatsapp'     => __('WhatsApp'),
                        default        => $state,
                    })
                    ->colors([
                        'primary' => 'general',
                        'success' => 'loyalty',
                        'warning' => 'payment',
                        'info'    => 'notification',
                        'gray'    => 'whatsapp',
                    ]),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('Description'))
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Mis à jour le'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label(__('Groupe'))
                    ->options([
                        'general'      => __('Général'),
                        'loyalty'      => __('Système de fidélité'),
                        'payment'      => __('Paiement'),
                        'notification' => __('Notifications'),
                        'whatsapp'     => __('WhatsApp'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultGroup('group');
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
