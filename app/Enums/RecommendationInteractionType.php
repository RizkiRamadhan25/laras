<?php

namespace App\Enums;

enum RecommendationInteractionType: string
{
    case Opened = 'opened';
    case FollowedUp = 'followed_up';
    case Dismissed = 'dismissed';
    case Irrelevant = 'irrelevant';

    public function label(): string
    {
        return match ($this) {
            self::Opened =>
                'Dibuka',

            self::FollowedUp =>
                'Ditindaklanjuti',

            self::Dismissed =>
                'Diingatkan nanti',

            self::Irrelevant =>
                'Tidak relevan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Opened =>
                'Rekomendasi dibuka oleh pengguna.',

            self::FollowedUp =>
                'Pengguna menyatakan rekomendasi telah ditindaklanjuti.',

            self::Dismissed =>
                'Rekomendasi ditunda untuk sementara.',

            self::Irrelevant =>
                'Pengguna menilai rekomendasi tidak relevan.',
        };
    }

    public function suppressionHours(): int
    {
        return match ($this) {
            self::Opened => 0,
            self::FollowedUp => 24 * 7,
            self::Dismissed => 24,
            self::Irrelevant => 24 * 30,
        };
    }

    public function suppressesRecommendation(): bool
    {
        return $this->suppressionHours() > 0;
    }
}
