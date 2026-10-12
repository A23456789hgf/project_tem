<?php

namespace App\Traits;

/**
 * Trait providing a deterministic, Unicode-aware normalization of project names.
 *
 * It performs only comparison-time transformations – the database values are never altered.
 *
 * ✅ التحسينات الجديدة:
 *   - إزالة المسافات غير المرئية (NBSP, ZWSP, ZWJ, BOM, RLM, LRM, ...).
 *   - توحيد الأرقام العربية الشرقية (٠-٩) والفارسية (۰-۹) إلى غربية (0-9).
 *   - توحيد الأحرف الفارسية (ک → ك، ی → ي).
 *   - توحيد اللام-ألف (لأ، لإ، لآ → لا).
 *   - دعم أشكال الهمزة المركبة (decomposed forms).
 *   - إزالة التطويل (Tatweel) بشكل موثوق.
 *   - إزالة التشكيل بشكل شامل (U+064B–U+065F، U+0670، U+0610–U+061A).
 *   - إزالة المزيد من علامات الترقيم والرموز.
 *   - ضمان أن المخرج لا يحتوي على أكثر من مسافة واحدة بين الكلمات.
 */
trait NormalizeProjectNameTrait
{
    /**
     * Normalize a project name for fuzzy comparison.
     *
     * Steps (order matters):
     *   1. Remove invisible Unicode spaces / control marks.
     *   2. Lower-case (multibyte safe).
     *   3. Unicode NFC normalization.
     *   4. Remove Tatweel (ـ) and Arabic diacritic marks.
     *   5. Replace Arabic Hamza variants with a plain "ا".
     *   6. Replace "ى" and "ئ" with "ي".
     *   7. Replace "ة" with "ه".
     *   8. Normalize Persian letters (ک، ی) to their Arabic equivalents.
     *   9. Normalize Lam-Alef ligatures (لأ، لإ، لآ) to "لا".
     *  10. Convert Eastern Arabic / Persian numerals to Western digits.
     *  11. Strip harmless punctuation, brackets, and dash-like characters.
     *  12. Collapse any remaining non-alphanumeric separators to a single space and trim.
     */
    public function normalizeProjectName(string $name): string
    {
        if ($name === '') {
            return '';
        }

        // 1. إزالة المسافات غير المرئية والرموز الخفية
        //    NBSP (00A0), ZWSP (200B), ZWNJ (200C), ZWJ (200D),
        //    LRM (200E), RLM (200F), BOM (FEFF),
        //    directional embeddings (202A–202E), word joiner (2060),
        //    narrow no-break space (202F), ideographic space (3000).
        $name = preg_replace(
            '/[\x{00A0}\x{200B}\x{200C}\x{200D}\x{200E}\x{200F}\x{FEFF}\x{202A}-\x{202E}\x{2060}\x{202F}\x{3000}]/u',
            ' ',
            $name
        );

        // 2. Lower-case (multibyte safe)
        $name = mb_strtolower($name, 'UTF-8');

        // 3. Unicode normalization (NFC) — يحوّل الأحرف المركبة إلى موحّدة
        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($name, \Normalizer::FORM_C);
            if ($normalized !== false) {
                $name = $normalized;
            }
        }

        // 4. إزالة التطويل (Tatweel) U+0640
        $name = preg_replace('/\x{0640}/u', '', $name);

        // 5. إزالة التشكيل والحركات
        //    U+064B–U+065F (Tanween, Harakat, Shadda, Sukun, ...)
        //    U+0670 (Alef Khanjariya)
        //    U+0610–U+061A (علامات قرآنية / صغيرة)
        $name = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0610}-\x{061A}\x{06D6}-\x{06ED}]/u', '', $name);

        // 6. توحيد الهمزات: أ إ آ ٱ ؤ ئ + الأشكال المركبة → ا و ي
        $hamzas = ['أ', 'إ', 'آ', 'ٱ', 'ٲ', 'ٳ', "ا\x{0653}", "ا\x{0654}", "ا\x{0655}", "ا\x{0670}"];
        $name = str_replace($hamzas, 'ا', $name);

        // 7. "ى" و "ئ" → "ي"
        $name = str_replace(['ى', 'ئ', "ي\x{0654}"], 'ي', $name);

        // 8. "ة" → "ه"
        $name = str_replace('ة', 'ه', $name);

        // 9. توحيد الأحرف الفارسية
        $name = str_replace(['ک', 'ڪ'], 'ك', $name);
        $name = str_replace(['ی', 'ﻯ', 'ﻰ'], 'ي', $name);
        $name = str_replace(['ۀ'], 'ه', $name);
        $name = str_replace(['ؤ'], 'و', $name);

        // 10. توحيد اللام-ألف بجميع أشكاله (لأ لإ لآ لا) → لا
        //     نستخدم التسلسلات المُركَّبة والمفردة على حد سواء
        $lamAlef = [
            'لأ'."\x{0654}", 'لإ'."\x{0655}", 'لآ'."\x{0653}",
            'لأ', 'لإ', 'لآ',
            'ﻷ', 'ﻹ', 'ﻵ', 'ﻻ', // Arabic Presentation Forms
        ];
        $name = str_replace($lamAlef, 'لا', $name);

        // 11. توحيد الأرقام العربية الشرقية (٠-٩) والفارسية (۰-۹) إلى غربية (0-9)
        $arabicIndic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $extendedArabic = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $name = str_replace($arabicIndic, $western, $name);
        $name = str_replace($extendedArabic, $western, $name);

        // 12. إزالة علامات الترقيم والرموز
        //     نستبدلها بمسافة لأنها قد تكون فاصلًا بين كلمات
        $puncts = [
            // شرطات وأشكالها
            '-', '‑', '‒', '–', '—', '―', '─', '_',
            // أقواس
            '(', ')', '[', ']', '{', '}', '<', '>', '«', '»', '‹', '›',
            // علامات ترقيم عربية/إنجليزية
            ',', ';', ':', '.', '·', '‧', '،', '؛', '؟', '?', '!', '¡',
            // علامات تنصيص
            '"', "'", '“', '”', '‘', '’', '`', '´', '٬', '٭',
            // رموز رياضية/عامة
            '/', '\\', '|', '+', '=', '*', '#', '@', '&', '^', '%', '$', '~',
            // أقواس زخرفية
            '«', '»', '‹', '›',
        ];
        $name = str_replace($puncts, ' ', $name);

        // 13. إزالة أي محرف تحكم غير مرئي متبقٍ
        $name = preg_replace('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', ' ', $name);

        // 14. دمج كل المسافات المتعددة إلى مسافة واحدة + trim
        $name = preg_replace('/\s+/u', ' ', $name);

        return trim($name);
    }

    /**
     * ✅ مفتاح مطابقة صارم جدًا: يزيل كل المسافات والرموز.
     * يبقى فقط الأحرف العربية/اللاتينية والأرقام.
     *
     * مفيد لمطابقة "مشروع أ" مع "مشروعأ" أو "م ش ر و ع أ".
     */
    public function normalizeProjectNameStrict(string $name): string
    {
        $normalized = $this->normalizeProjectName($name);

        // إزالة كل ما ليس حرفًا عربيًا أو لاتينيًا أو رقمًا
        $normalized = preg_replace('/[^\p{Arabic}\p{Latin}\p{N}]/u', '', $normalized);

        return $normalized;
    }
}
