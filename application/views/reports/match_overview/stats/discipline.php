<!-- type:discipline / discipline.php -->

<?php
// ---------- discipline metrics ----------
$home_fouls_conceded = $home_metrics['discipline']['fouls_conceded'] ?? null;
$away_fouls_conceded = $away_metrics['discipline']['fouls_conceded'] ?? null;
$total_fouls_conceded = ($home_fouls_conceded ?? 0) + ($away_fouls_conceded ?? 0);
$home_fouls_conceded_pct = $total_fouls_conceded > 0 ? ($home_fouls_conceded / $total_fouls_conceded * 100) : null;

$home_yellow_cards = $home_metrics['discipline']['yellow_cards'] ?? null;
$away_yellow_cards = $away_metrics['discipline']['yellow_cards'] ?? null;
$total_yellow_cards = ($home_yellow_cards ?? 0) + ($away_yellow_cards ?? 0);
$home_yellow_cards_pct = $total_yellow_cards > 0 ? ($home_yellow_cards / $total_yellow_cards * 100) : null;

$home_red_cards = $home_metrics['discipline']['red_cards'] ?? null;
$away_red_cards = $away_metrics['discipline']['red_cards'] ?? null;
$total_red_cards = ($home_red_cards ?? 0) + ($away_red_cards ?? 0);
$home_red_cards_pct = $total_red_cards > 0 ? ($home_red_cards / $total_red_cards * 100) : null;
?>

<!-- Discipline section -->
<div class="w-full flex flex-col items-center px-20 py-8 gap-4">
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Fouls conceded</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_fouls_conceded !== null ? number_format($home_fouls_conceded, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_fouls_conceded_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_fouls_conceded_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_fouls_conceded !== null ? number_format($away_fouls_conceded, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Yellow cards</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_yellow_cards !== null ? number_format($home_yellow_cards, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_yellow_cards_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_yellow_cards_pct,0),100) ?>%; background:linear-gradient(90deg,#f6c84c,#f0b429); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_yellow_cards !== null ? number_format($away_yellow_cards, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Red cards</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_red_cards !== null ? number_format($home_red_cards, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_red_cards_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_red_cards_pct,0),100) ?>%; background:linear-gradient(90deg,#d9534f,#f46b6b); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_red_cards !== null ? number_format($away_red_cards, 0) : '—' ?>
            </span>
        </div>
    </div>
</div>
