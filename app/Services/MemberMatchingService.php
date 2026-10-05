<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Str;

class MemberMatchingService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{member: Member|null, ambiguous: bool}
     */
    public function matchForRegistration(array $data): array
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $phone = $this->normalizePhone((string) ($data['phone'] ?? ''));
        $fullName = trim((string) ($data['full_name'] ?? ''));

        $candidates = Member::query()->get();

        if ($email !== '') {
            $candidates = $candidates->filter(fn (Member $member): bool => strtolower((string) ($member->email ?? '')) === $email);
        }

        if ($phone !== '') {
            $candidates = $candidates->filter(fn (Member $member): bool => $this->normalizePhone((string) ($member->phone ?? '')) === $phone)
                ->when($candidates->isNotEmpty(), fn ($filtered) => $filtered, fn ($filtered) => Member::query()->get()->filter(fn (Member $member): bool => $this->normalizePhone((string) ($member->phone ?? '')) === $phone));
        }

        if ($fullName !== '' && $candidates->isEmpty()) {
            $candidates = Member::query()->get()->filter(function (Member $member) use ($fullName): bool {
                $memberName = $this->normalizeName((string) ($member->name ?? ''));
                $candidateName = $this->normalizeName($fullName);

                if ($memberName === '' || $candidateName === '') {
                    return false;
                }

                return $memberName === $candidateName
                    || Str::contains($memberName, $candidateName)
                    || Str::contains($candidateName, $memberName);
            });
        }

        if ($candidates->count() > 1) {
            return ['member' => null, 'ambiguous' => true];
        }

        if ($candidates->count() === 1) {
            return ['member' => $candidates->first(), 'ambiguous' => false];
        }

        return ['member' => null, 'ambiguous' => false];
    }

    protected function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        $digits = preg_replace('/^00/', '', $digits);

        if (str_starts_with($digits, '243')) {
            $digits = '0'.substr($digits, 3);
        }

        return $digits;
    }

    protected function normalizeName(string $value): string
    {
        $normalized = trim(strtolower((string) $value));

        $normalized = preg_replace('/[\p{M}]/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^\pL\pN\s]/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        return trim($normalized);
    }
}
