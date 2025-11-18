<!-- type:attacking / attacking.php -->

<?php
// ---------- attack metrics ----------
$home_goals = $home_metrics['attack']['goals'] ?? null;
$away_goals = $away_metrics['attack']['goals'] ?? null;
$total_goals = ($home_goals ?? 0) + ($away_goals ?? 0);
$home_goals_pct = $total_goals > 0 ? ($home_goals / $total_goals * 100) : null;

$home_shots = $home_metrics['attack']['shots'] ?? null;
$away_shots = $away_metrics['attack']['shots'] ?? null;
$total_shots = ($home_shots ?? 0) + ($away_shots ?? 0);
$home_shots_pct = $total_shots > 0 ? ($home_shots / $total_shots * 100) : null;

$home_shots_on_target = $home_metrics['attack']['shots_on_target'] ?? null;
$away_shots_on_target = $away_metrics['attack']['shots_on_target'] ?? null;
$total_shots_on_target = ($home_shots_on_target ?? 0) + ($away_shots_on_target ?? 0);
$home_shots_on_target_pct = $total_shots_on_target > 0 ? ($home_shots_on_target / $total_shots_on_target * 100) : null;

$home_shots_off_target = $home_metrics['attack']['shots_off_target'] ?? null;
$away_shots_off_target = $away_metrics['attack']['shots_off_target'] ?? null;
$total_shots_off_target = ($home_shots_off_target ?? 0) + ($away_shots_off_target ?? 0);
$home_shots_off_target_pct = $total_shots_off_target > 0 ? ($home_shots_off_target / $total_shots_off_target * 100) : null;

$home_blocked_shots = $home_metrics['attack']['blocked_shots'] ?? null;
$away_blocked_shots = $away_metrics['attack']['blocked_shots'] ?? null;
$total_blocked_shots = ($home_blocked_shots ?? 0) + ($away_blocked_shots ?? 0);
$home_blocked_shots_pct = $total_blocked_shots > 0 ? ($home_blocked_shots / $total_blocked_shots * 100) : null;

$home_sca = $home_metrics['attack']['shot_creating_actions'] ?? null;
$away_sca = $away_metrics['attack']['shot_creating_actions'] ?? null;
$total_sca = ($home_sca ?? 0) + ($away_sca ?? 0);
$home_sca_pct = $total_sca > 0 ? ($home_sca / $total_sca * 100) : null;
?>

<!-- Attack section -->
<div class="w-full flex flex-col items-center px-20 py-8 gap-4">
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Goals</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_goals !== null ? number_format($home_goals, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_goals_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_goals_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_goals !== null ? number_format($away_goals, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Shots</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_shots !== null ? number_format($home_shots, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_shots_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_shots_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_shots !== null ? number_format($away_shots, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Shots on target</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_shots_on_target !== null ? number_format($home_shots_on_target, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_shots_on_target_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_shots_on_target_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_shots_on_target !== null ? number_format($away_shots_on_target, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Shots off target</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_shots_off_target !== null ? number_format($home_shots_off_target, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_shots_off_target_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_shots_off_target_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_shots_off_target !== null ? number_format($away_shots_off_target, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Blocked shots</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_blocked_shots !== null ? number_format($home_blocked_shots, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_blocked_shots_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_blocked_shots_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_blocked_shots !== null ? number_format($away_blocked_shots, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Shot-creating actions</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_sca !== null ? number_format($home_sca, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_sca_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_sca_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_sca !== null ? number_format($away_sca, 0) : '—' ?>
            </span>
        </div>
    </div>
</div>
