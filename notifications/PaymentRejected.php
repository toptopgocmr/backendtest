<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public string  $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ Paiement non confirmé – Action requise')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre paiement de **{$this->payment->amount} {$this->payment->currency}** n'a pas pu être confirmé.")
            ->line("**Motif :** {$this->reason}")
            ->line("**ID Transaction soumis :** {$this->payment->transaction_id}")
            ->action('Soumettre à nouveau', url("/bookings/{$this->payment->booking_id}/payment"))
            ->line("Si vous pensez qu'il s'agit d'une erreur, contactez-nous directement.")
            ->salutation('Cordialement, l\'équipe support');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'payment_rejected',
            'payment_id'  => $this->payment->id,
            'booking_id'  => $this->payment->booking_id,
            'booking_ref' => $this->payment->booking->reference ?? "BK-{$this->payment->booking_id}",
            'reason'      => $this->reason,
            'message'     => "Votre paiement a été refusé. Motif : {$this->reason}",
        ];
    }
}
