<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationSettingResource\Pages;
use App\Filament\Resources\NotificationSettingResource\RelationManagers;
use App\Models\NotificationSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotificationSettingResource extends Resource
{
    protected static ?string $model = NotificationSetting::class;

    protected static ?string $slug = 'notification-settings';

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Paramètres Notifications';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 10;

    protected static ?string $pluralModelLabel = 'Paramètres de notifications';

    protected static ?string $modelLabel = 'Paramètre de notification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Clé')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Identifiant unique pour ce paramètre (ex: booking_confirmed)'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nom affiché dans l\'interface admin'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->helperText('Description de la notification'),
                    ]),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'push' => 'Notifications Push',
                                'email' => 'Notifications Email',
                                'sms' => 'Notifications SMS',
                                'promo' => 'Promotions',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('enabled')
                            ->label('Activé')
                            ->default(true)
                            ->helperText('Active ou désactive cette notification globalement'),

                        Forms\Components\Toggle::make('user_can_disable')
                            ->label('L\'utilisateur peut désactiver')
                            ->default(true)
                            ->helperText('Permet aux utilisateurs de désactiver ce type de notification'),
                    ]),

                Forms\Components\Section::make('Métadonnées')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Métadonnées JSON')
                            ->helperText('Configuration supplémentaire au format clé-valeur'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('Clé')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'push' => 'Push',
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'promo' => 'Promo',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'push',
                        'success' => 'email',
                        'warning' => 'sms',
                        'danger' => 'promo',
                    ]),

                Tables\Columns\IconColumn::make('enabled')
                    ->label('Activé')
                    ->boolean(),

                Tables\Columns\IconColumn::make('user_can_disable')
                    ->label('Désactivable')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'push' => 'Notifications Push',
                        'email' => 'Notifications Email',
                        'sms' => 'Notifications SMS',
                        'promo' => 'Promotions',
                    ]),

                Tables\Filters\TernaryFilter::make('enabled')
                    ->label('Activé'),

                Tables\Filters\TernaryFilter::make('user_can_disable')
                    ->label('Désactivable par l\'utilisateur'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('category');
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
            'index' => Pages\ListNotificationSettings::route('/'),
            'create' => Pages\CreateNotificationSetting::route('/create'),
            'edit' => Pages\EditNotificationSetting::route('/{record}/edit'),
        ];
    }
}
