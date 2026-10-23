<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiSeoService
{
    private string $apiKey;
    private string $model;
    private string $endpoint;

    public function __construct()
    {
        $this->apiKey   = config('gemini.api_key', '');
        $this->model    = config('gemini.model', 'gemini-1.5-flash');
        $this->endpoint = config('gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta/models');
    }

    /**
     * Calls Gemini API to generate viral YouTube/Facebook SEO content.
     *
     * @param  string  $title
     * @param  string  $description
     * @param  string  $language
     * @return array{seo_title: string, description: string, tags: array, hashtags: array, chapters: array, keywords: array}
     */
    public function generateVideoSeo(string $title, string $description = '', string $language = 'bn'): array
    {
        $prompt = <<<PROMPT
Generate viral YouTube/Facebook SEO content for a Bangladeshi news/media video.
Given title: "{$title}", description: "{$description}".

Generate in BOTH Bengali and English mixed style (like Bangladeshi YouTube channels use).

Return ONLY a valid JSON object (no markdown, no explanation) with these exact keys:
- seo_title: string — Viral optimized title (max 100 chars)
- description: string — Full SEO description with keywords (500 chars)
- tags: array — Array of 25 tags (mix of Bengali & English)
- hashtags: array — Array of 15 hashtags (# prefix, mix of Bengali & English trending hashtags)
- chapters: array — Array of suggested chapter objects [{time: "0:00", title: "Introduction"}, ...]
- keywords: array — Array of 10 primary keywords
PROMPT;

        try {
            $url = "{$this->endpoint}/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(30)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature'      => 0.7,
                    'maxOutputTokens'  => 2048,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if (!$response->successful()) {
                Log::warning('Gemini API non-200 response', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->fallbackSeo($title);
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Strip markdown code fences if present
            $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
            $text = preg_replace('/\s*```$/', '', $text);
            $text = trim($text);

            if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                $text = $matches[0];
            }

            $parsed = json_decode($text, true);

            if (!is_array($parsed)) {
                Log::warning('Gemini SEO: JSON parse failed', ['raw' => $text]);
                return $this->fallbackSeo($title);
            }

            // Ensure required keys exist with defaults
            return array_merge($this->fallbackSeo($title), $parsed);

        } catch (\Throwable $e) {
            Log::error('Gemini SEO generation failed', ['error' => $e->getMessage()]);
            return $this->fallbackSeo($title);
        }
    }

    /**
     * Suggests thumbnail text overlays for a given title.
     *
     * @return array{main_text: string, sub_text: string, call_to_action: string}
     */
    public function suggestThumbnailText(string $title): array
    {
        $prompt = <<<PROMPT
Given this Bangladeshi news video title: "{$title}", suggest thumbnail text overlays.
Return ONLY a valid JSON object with:
- main_text: string (short punchy main text for thumbnail, max 30 chars, Bengali/English)
- sub_text: string (supporting text, max 40 chars)
- call_to_action: string (e.g. "দেখুন" / "Watch Now", max 15 chars)
PROMPT;

        try {
            $url = "{$this->endpoint}/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(15)->post($url, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if (!$response->successful()) {
                return $this->fallbackThumbnail($title);
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
            $text = preg_replace('/\s*```$/', '', $text);
            $text = trim($text);

            if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                $text = $matches[0];
            }

            $parsed = json_decode(trim($text), true);
            return is_array($parsed) ? $parsed : $this->fallbackThumbnail($title);

        } catch (\Throwable $e) {
            Log::error('Gemini thumbnail suggestion failed', ['error' => $e->getMessage()]);
            return $this->fallbackThumbnail($title);
        }
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function fallbackSeo(string $title): array
    {
        return [
            'seo_title'   => $title,
            'description' => $title . ' | বাংলাদেশের সর্বশেষ সংবাদ। আমাদের চ্যানেল সাবস্ক্রাইব করুন।',
            'tags'        => ['বাংলাদেশ', 'news', 'bangla news', 'latest news', 'bd news'],
            'hashtags'    => ['#বাংলাদেশ', '#BanglaNews', '#LatestNews', '#BDNews', '#Breaking'],
            'chapters'    => [
                ['time' => '0:00', 'title' => 'Introduction'],
                ['time' => '0:30', 'title' => 'Main Story'],
            ],
            'keywords'    => ['বাংলাদেশ', 'news', 'bangla', 'latest', 'breaking'],
        ];
    }

    private function fallbackThumbnail(string $title): array
    {
        $short = mb_substr($title, 0, 28);
        return [
            'main_text'       => $short,
            'sub_text'        => 'সর্বশেষ সংবাদ | Latest News',
            'call_to_action'  => 'দেখুন',
        ];
    }
}
