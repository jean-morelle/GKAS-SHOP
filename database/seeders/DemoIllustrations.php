<?php

namespace Database\Seeders;

/**
 * Génère des illustrations SVG locales (aucune image distante) pour les
 * produits et logos de démonstration. Les SVG sont des fichiers statiques
 * produits par la plateforme : aucun SVG n'est accepté en téléversement.
 */
class DemoIllustrations
{
    /** Formes simples dans un repère 240×240. */
    private const SHAPES = [
        'tshirt' => '<path d="M72 52 L102 40 Q120 58 138 40 L168 52 L192 92 L162 104 L162 186 L78 186 L78 104 L48 92 Z" />',
        'dress' => '<path d="M102 36 L138 36 L134 78 Q150 90 178 188 L62 188 Q90 90 106 78 Z" /><path d="M102 36 Q120 52 138 36" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width="4"/>',
        'shirt' => '<path d="M74 50 L104 40 L120 60 L136 40 L166 50 L186 100 L160 108 L160 188 L80 188 L80 108 L54 100 Z" /><path d="M120 60 L120 188" stroke="#fff" stroke-opacity=".5" stroke-width="3"/>',
        'pants' => '<path d="M84 40 L156 40 L164 190 L132 190 L120 92 L108 190 L76 190 Z" />',
        'boubou' => '<path d="M60 70 L104 42 Q120 56 136 42 L180 70 L180 186 L60 186 Z" /><circle cx="120" cy="120" r="18" fill="#fff" fill-opacity=".35"/>',
        'bag' => '<path d="M92 84 Q92 44 120 44 Q148 44 148 84" fill="none" stroke="currentColor" stroke-width="10"/><rect x="58" y="80" width="124" height="108" rx="14" />',
        'watch' => '<rect x="100" y="30" width="40" height="180" rx="10" fill-opacity=".6"/><circle cx="120" cy="120" r="46" /><circle cx="120" cy="120" r="34" fill="#fff" fill-opacity=".85"/><path d="M120 120 L120 98 M120 120 L136 128" stroke="#334155" stroke-width="4" stroke-linecap="round"/>',
        'glasses' => '<circle cx="82" cy="122" r="32" /><circle cx="158" cy="122" r="32" /><path d="M114 118 Q120 110 126 118" fill="none" stroke="currentColor" stroke-width="7"/><path d="M50 116 L32 100 M190 116 L208 100" stroke="currentColor" stroke-width="7" stroke-linecap="round"/>',
        'necklace' => '<path d="M56 56 Q120 190 184 56" fill="none" stroke="currentColor" stroke-width="7"/><path d="M120 150 L136 172 L120 196 L104 172 Z" />',
        'shoe' => '<path d="M40 150 L40 104 Q60 98 78 112 Q104 132 150 140 Q200 148 202 172 L202 182 L40 182 Z" /><rect x="40" y="176" width="162" height="10" rx="4" fill-opacity=".6"/>',
        'cap' => '<path d="M58 140 Q58 70 120 70 Q182 70 182 140 Z" /><path d="M150 140 L214 150 Q214 160 150 158 Z" fill-opacity=".7"/>',
        'earrings' => '<circle cx="90" cy="70" r="8"/><circle cx="150" cy="70" r="8"/><path d="M90 78 L70 150 L110 150 Z"/><path d="M150 78 L130 150 L170 150 Z"/>',
    ];

    public static function product(string $shape, string $from, string $to, string $label): string
    {
        $shapeSvg = self::SHAPES[$shape] ?? self::SHAPES['bag'];
        $label = htmlspecialchars($label, ENT_QUOTES | ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" width="800" height="800">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$from}"/><stop offset="1" stop-color="{$to}"/></linearGradient></defs>
  <rect width="240" height="240" fill="url(#g)"/>
  <circle cx="200" cy="30" r="60" fill="#fff" fill-opacity=".08"/>
  <g fill="#ffffff" fill-opacity=".92" color="#ffffff" transform="translate(0,-8)">{$shapeSvg}</g>
  <text x="120" y="226" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="11" fill="#ffffff" fill-opacity=".85">{$label} · illustration</text>
</svg>
SVG;
    }

    public static function logo(string $initials, string $color): string
    {
        $initials = htmlspecialchars($initials, ENT_QUOTES | ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="400" height="400">
  <rect width="200" height="200" rx="100" fill="{$color}"/>
  <circle cx="100" cy="100" r="78" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="4"/>
  <text x="100" y="122" text-anchor="middle" font-family="Georgia, serif" font-size="64" font-weight="bold" fill="#fff">{$initials}</text>
</svg>
SVG;
    }

    public static function banner(string $from, string $to): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 500" width="1600" height="500" preserveAspectRatio="xMidYMid slice">
  <defs><linearGradient id="b" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$from}"/><stop offset="1" stop-color="{$to}"/></linearGradient></defs>
  <rect width="1600" height="500" fill="url(#b)"/>
  <g fill="#fff" fill-opacity=".07">
    <circle cx="1350" cy="80" r="220"/><circle cx="1500" cy="420" r="160"/><circle cx="200" cy="520" r="200"/>
  </g>
  <g stroke="#fff" stroke-opacity=".1" stroke-width="18" fill="none">
    <path d="M900 0 L1100 500"/><path d="M980 0 L1180 500"/><path d="M1060 0 L1260 500"/>
  </g>
</svg>
SVG;
    }
}
