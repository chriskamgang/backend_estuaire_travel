<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Page de configuration WhatsApp (UltraMsg)
 * Accessible via le panneau Filament admin
 */
class WhatsAppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?string $navigationLabel = 'WhatsApp';
    protected static ?int    $navigationSort  = 98;
    protected static string  $view            = 'filament.pages.whatsapp-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'whatsapp_enabled'             => (bool) Setting::get('whatsapp_enabled', false),
            'whatsapp_instance_id'         => Setting::get('whatsapp_instance_id', ''),
            'whatsapp_token'               => Setting::get('whatsapp_token', ''),
            'whatsapp_booking_message'     => Setting::get(
                'whatsapp_booking_message',
                "✅ *Réservation confirmée !*\n\nBonjour {{passenger_name}},\n\nVotre réservation sur le trajet *{{from}} → {{to}}* est confirmée.\n\n📅 Date : {{date}}\n⏰ Départ : {{time}}\n🪑 Places : {{seats}}\n💰 Total : {{price}} FCFA\n📍 Point de prise en charge : {{pickup}}\n\n🚗 Conducteur : {{driver_name}}\n📞 Téléphone : {{driver_phone}}\n\nBon voyage avec Estuaire Travel ! 🌿"
            ),
            'whatsapp_bus_booking_message' => Setting::get(
                'whatsapp_bus_booking_message',
                "🎟️ *Réservation bus confirmée !*\n\nBonjour {{passenger_name}},\n\nVotre réservation est enregistrée avec succès.\n\n🚌 Trajet : *{{from}} → {{to}}*\n📅 Date : {{date}}\n⏰ Départ : {{time}}\n🪑 Siège(s) : {{seats}}\n💰 Total : {{price}} FCFA\n🏢 Compagnie : {{company}}\n🔖 Référence : {{reference}}\n\nPrésentez cette référence au guichet pour récupérer votre billet.\n\nBon voyage avec Estuaire Travel ! 🌿"
            ),
            'test_phone' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Connexion UltraMsg')
                    ->description('Configurez votre compte UltraMsg pour envoyer des messages WhatsApp automatiquement.')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Toggle::make('whatsapp_enabled')
                            ->label('Activer les notifications WhatsApp')
                            ->helperText('Les messages seront envoyés automatiquement aux clients après chaque réservation confirmée.')
                            ->columnSpanFull(),

                        TextInput::make('whatsapp_instance_id')
                            ->label('Instance ID')
                            ->placeholder('instance12345')
                            ->helperText('Visible dans votre tableau de bord UltraMsg → https://ultramsg.com')
                            ->maxLength(100),

                        TextInput::make('whatsapp_token')
                            ->label("Token d'authentification")
                            ->password()
                            ->revealable()
                            ->placeholder('••••••••••••••••')
                            ->helperText('Token API de votre instance UltraMsg')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Message — Réservation covoiturage')
                    ->description('Envoyé au passager quand le chauffeur confirme sa réservation covoiturage.')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        Textarea::make('whatsapp_booking_message')
                            ->label('Template du message covoiturage')
                            ->helperText('Variables : {{passenger_name}}, {{from}}, {{to}}, {{date}}, {{time}}, {{seats}}, {{price}}, {{pickup}}, {{driver_name}}, {{driver_phone}}')
                            ->rows(10)
                            ->columnSpanFull()
                            ->required(),
                    ]),

                Section::make('Message — Réservation bus')
                    ->description('Envoyé au passager immédiatement après la création de sa réservation de bus (sans info chauffeur).')
                    ->icon('heroicon-o-ticket')
                    ->schema([
                        Textarea::make('whatsapp_bus_booking_message')
                            ->label('Template du message bus')
                            ->helperText('Variables : {{passenger_name}}, {{from}}, {{to}}, {{date}}, {{time}}, {{seats}}, {{price}}, {{company}}, {{reference}}')
                            ->rows(10)
                            ->columnSpanFull()
                            ->required(),
                    ]),

                Section::make('Test de connexion')
                    ->description('Envoyez un message de test pour vérifier votre configuration avant de la mettre en production.')
                    ->icon('heroicon-o-paper-airplane')
                    ->schema([
                        TextInput::make('test_phone')
                            ->label('Numéro de téléphone pour le test')
                            ->placeholder('+24106XXXXXXX')
                            ->helperText('Ce numéro WhatsApp recevra le message de test')
                            ->tel(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('whatsapp_enabled', !empty($data['whatsapp_enabled']) ? '1' : '0');
        Setting::set('whatsapp_instance_id', $data['whatsapp_instance_id'] ?? '');
        Setting::set('whatsapp_token', $data['whatsapp_token'] ?? '');
        Setting::set('whatsapp_booking_message', $data['whatsapp_booking_message'] ?? '');
        Setting::set('whatsapp_bus_booking_message', $data['whatsapp_bus_booking_message'] ?? '');

        Notification::make()
            ->title('Paramètres sauvegardés')
            ->body('La configuration WhatsApp a été mise à jour avec succès.')
            ->success()
            ->send();
    }

    public function sendTestMessage(): void
    {
        $data = $this->form->getState();
        $phone = trim($data['test_phone'] ?? '');

        if (empty($phone)) {
            Notification::make()
                ->title('Numéro manquant')
                ->body('Veuillez saisir un numéro de téléphone pour le test.')
                ->warning()
                ->send();
            return;
        }

        // Sauvegarder d'abord les credentials
        $this->save();

        // Recréer le service avec les nouveaux settings
        $service = new WhatsAppService();
        $result  = $service->sendTestMessage($phone);

        if ($result['success']) {
            Notification::make()
                ->title('Message de test envoyé !')
                ->body('Vérifiez votre WhatsApp : ' . $phone)
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title("Échec de l'envoi")
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label('Envoyer un test')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->action('sendTestMessage'),

            Action::make('save')
                ->label('Sauvegarder')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $enabled = Setting::get('whatsapp_enabled', false);
        return $enabled ? 'Actif' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
