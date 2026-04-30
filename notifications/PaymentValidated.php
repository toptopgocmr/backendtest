<?php
// ─────────────────────────────────────────────────────────────────────────────
// App\Notifications\PaymentValidated.php
// ─────────────────────────────────────────────────────────────────────────────

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentValidated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail']; // ajouter 'vonage' pour SMS si nécessaire
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ Paiement confirmé – ' . ($this->payment->booking->reference ?? "BK-{$this->payment->booking_id}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre paiement de **{$this->payment->amount} {$this->payment->currency}** a été confirmé.")
            ->line("Référence réservation : **" . ($this->payment->booking->reference ?? "BK-{$this->payment->booking_id}") . "**")
            ->line("ID Transaction : **{$this->payment->transaction_id}**")
            ->line("Date de confirmation : **{$this->payment->verified_at?->format('d/m/Y à H:i')}**")
            ->action('Voir ma réservation', url("/bookings/{$this->payment->booking_id}"))
            ->line('Merci pour votre confiance !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'payment_validated',
            'payment_id'     => $this->payment->id,
            'booking_id'     => $this->payment->booking_id,
            'booking_ref'    => $this->payment->booking->reference ?? "BK-{$this->payment->booking_id}",
            'amount'         => $this->payment->amount,
            'currency'       => $this->payment->currency,
            'transaction_id' => $this->payment->transaction_id,
            'verified_at'    => $this->payment->verified_at?->toISOString(),
            'message'        => 'Votre paiement a été confirmé. Votre réservation est active !',
        ];
    }
}
