<!-- type:general / general.php -->
<?php
// reuse variables from match_overview parent view (or pass $home_metrics,$away_metrics separately)
$home_pos_pct  = $home_metrics['possession']['possession_pct'] ?? null;
$away_pos_pct  = $away_metrics['possession']['possession_pct'] ?? null;
// duels
$home_duels  = $home_metrics['general']['duels'] ?? null;
$away_duels  = $away_metrics['general']['duels'] ?? null;
$total_duels = $home_duels + $away_duels;
$home_duels_pct = $home_duels / $total_duels * 100;
// duels won
$home_duels_won = $home_metrics['general']['duels_won'] ?? null;
$away_duels_won  = $away_metrics['general']['duels_won'] ?? null;
$total_duels_won = $home_duels_won + $away_duels_won;
$home_duels_won_pct = $home_duels_won / $total_duels_won * 100;
// duels_success_rate_pct
$home_duels_success_rate_pct = $home_metrics['general']['duels_success_rate_pct'];
$away_duels_success_rate_pct = $away_metrics['general']['duels_success_rate_pct'];
// aerial_duels
$home_aerial_duels = $home_metrics['general']['aerial_duels'];
$away_aerial_duels = $away_metrics['general']['aerial_duels'];
$total_aerial_duels = $home_aerial_duels + $away_aerial_duels;
$home_aerial_duels_pct = $home_aerial_duels / $total_aerial_duels * 100;
// ground_duels
$home_ground_duels = $home_metrics['general']['ground_duels'];
$away_ground_duels = $away_metrics['general']['ground_duels'];
$total_ground_duels = $home_ground_duels + $away_ground_duels;
$home_ground_duels_pct = $home_ground_duels / $total_ground_duels * 100;
// offsides
$home_offsides = $home_metrics['general']['offsides'];
$away_offsides = $away_metrics['general']['offsides'];
$total_offsides = $home_offsides + $away_offsides;
$home_offsides_pct = $home_offsides / $total_offsides * 100;
// corner awarded
$home_corner_awarded = $home_metrics['general']['corner_awarded'];
$away_corner_awarded = $away_metrics['general']['corner_awarded'];
$total_corner_awarded = $home_corner_awarded + $away_corner_awarded;
$home_corner_awarded_pct = $home_corner_awarded / $total_corner_awarded * 100;
?>

<div class="w-full flex flex-col items-center px-20 py-8 gap-4">
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Possesion</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_pos_pct !== null ? number_format($home_pos_pct, 0) . '%' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_pos_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_pos_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_pos_pct !== null ? number_format($away_pos_pct, 0) . '%' : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Duels</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_duels !== null ? number_format($home_duels, 0) . '' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_duels !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_duels_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_duels !== null ? number_format($away_duels, 0) . '' : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Duels won</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_duels_won !== null ? number_format($home_duels_won, 0) . '' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_duels_won_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_duels_won_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_duels_won !== null ? number_format($away_duels_won, 0) . '' : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Duels success rate</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_duels_success_rate_pct !== null ? number_format($home_duels_success_rate_pct, 0) . '%' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_duels_success_rate_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_duels_success_rate_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_duels_success_rate_pct !== null ? number_format($away_duels_success_rate_pct, 0) . '%' : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Aerial Duels</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_aerial_duels !== null ? number_format($home_aerial_duels, 0) . '' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_aerial_duels_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_aerial_duels_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_aerial_duels !== null ? number_format($away_aerial_duels, 0) . '' : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Ground Duels</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_ground_duels !== null ? number_format($home_ground_duels, 0) . '' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_ground_duels_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_ground_duels_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_ground_duels !== null ? number_format($away_ground_duels, 0) . '' : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Offsides</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_offsides !== null ? number_format($home_offsides, 0) . '' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_offsides_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_offsides_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_offsides !== null ? number_format($away_offsides, 0) . '' : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Corners awarded</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_corner_awarded !== null ? number_format($home_corner_awarded, 0) . '' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_corner_awarded_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_corner_awarded_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_corner_awarded !== null ? number_format($away_corner_awarded, 0) . '' : '—' ?>
            </span>
        </div>
    </div>
</div>