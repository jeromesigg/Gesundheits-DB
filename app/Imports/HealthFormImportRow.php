<?php

namespace App\Imports;

use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Eine Zeile der Teilnehmerliste (Vorlage public/files/vorlage.xlsx, Spalten als Slug der Überschrift).
 */
class HealthFormImportRow
{
    /**
     * Felder für HealthForm, getrimmt; Ceviname fällt auf den Vornamen zurück.
     *
     * @param  array<string, mixed>  $row
     * @return array{first_name: string, last_name: string, nickname: string, street: string, zip_code: string, city: string, birthday: ?string, ahv: string, group_text: string}
     */
    public static function attributes(array $row): array
    {
        $text = fn (string $column): string => trim((string) ($row[$column] ?? ''));
        $firstName = $text('vorname');

        return [
            'first_name' => $firstName,
            'last_name' => $text('nachname'),
            'nickname' => $text('ceviname') !== '' ? $text('ceviname') : $firstName,
            'street' => $text('strasse'),
            'zip_code' => $text('plz'),
            'city' => $text('ort'),
            'birthday' => self::parseBirthday($row['geburtstag'] ?? null),
            'ahv' => $text('ahv_nr'),
            'group_text' => $text('abteilung'),
        ];
    }

    public static function isEmpty(mixed $value): bool
    {
        return trim((string) $value) === '';
    }

    /**
     * Geburtstag als Y-m-d. Erlaubt: Excel-Datum (Zahl), TT.MM.JJJJ, T.M.JJJJ, TT/MM/JJJJ, JJJJ-MM-TT.
     * Alles andere (zweistellige Jahre, 31.02., US-Format, Zukunft, vor 1900) ergibt null statt eines geratenen Datums.
     */
    public static function parseBirthday(mixed $value): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }
        $value = trim((string) $value);

        if (is_numeric($value)) {
            if ((float) $value < 1) {
                return null;
            }
            $date = DateTimeImmutable::createFromMutable(Date::excelToDateTimeObject((float) $value));

            return self::plausible($date);
        }

        if (preg_match('/^(\d{1,2})[.\/](\d{1,2})[.\/](\d{4})$/', $value, $m)) {
            [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
            [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return self::plausible(new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day)));
    }

    private static function plausible(DateTimeImmutable $date): ?string
    {
        if ($date->format('Y') < 1900 || $date > new DateTimeImmutable('today')) {
            return null;
        }

        return $date->format('Y-m-d');
    }
}
