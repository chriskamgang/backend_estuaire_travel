<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('Compagnies');
    }

    public static function getModelLabel(): string
    {
        return __('Compagnie');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Compagnies');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_companies');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_companies');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('edit_companies');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_companies');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_companies');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Nom'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('logo')
                    ->label(__('Logo'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('rating')
                    ->label(__('Note'))
                    ->required()
                    ->numeric()
                    ->default(4.00),
                Forms\Components\TextInput::make('total_reviews')
                    ->label(__('Total des avis'))
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('phone')
                    ->label(__('Téléphone'))
                    ->tel()
                    ->maxLength(20)
                    ->default(null),
                Forms\Components\TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('address')
                    ->label(__('Adresse'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Nom'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('logo')
                    ->label(__('Logo'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label(__('Note'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_reviews')
                    ->label(__('Total des avis'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Téléphone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
