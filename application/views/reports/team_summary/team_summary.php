<?php
// Helpers remain the same
function fmt_rating($v) {
    if ($v === null || $v === '') return '—';
    return number_format((float)$v, 1);
}

function rating_pct($v) {
    if ($v === null || $v === '') return 0;
    $n = (float)$v;
    $n = max(0, min(10, $n)); 
    return round(($n / 10) * 100, 1);
}

function score_to_hex($v) {
    if (!is_numeric($v)) return '#94a3b8'; // gray
    $s = (float)$v;
    if ($s >= 8.0) return '#34d399';
    if ($s >= 6.0) return '#a3e635';
    if ($s >= 4.0) return '#facc15';
    return '#f87171';
}

?>

<div class="w-full h-full flex flex-col gap-4 font-sans antialiased text-slate-200">
  
  <div class="w-full flex flex-col bg-[#0f0f10] border border-white/6 rounded-3xl shadow-xl relative">
    
    <div class="px-6 py-5 border-b border-white/6 bg-white/[0.02] backdrop-blur-md z-10">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-gradient-to-br from-indigo-500/20 to-purple-500/20 shadow-inner border border-white/6">
          <svg class="w-6 h-6 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M12 2v4M5 9v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div>
          <h2 class="text-white text-lg font-bold tracking-tight">Team Summary</h2>
          <p class="text-sm text-slate-400">Performance ratings & insights</p>
        </div>
      </div>
    </div>

    <div class="flex flex-col p-4 space-y-3">
    <?php
    $render_section = function($id, $label, $rating_key) use ($team_ratings) {
        $rating_val = $team_ratings[$rating_key] ?? null;
        $pct = rating_pct($rating_val);
        $rating_display = fmt_rating($rating_val);
        
        // IDs
        $wrapper_id   = "wrapper-{$id}";   // For height animation
        $container_id = "summary-container-{$id}"; // For AJAX content
        $btn_id       = "{$id}-btn";

        $data_rating_attr = is_numeric($rating_val) ? (float)$rating_val : '';
        $data_pct_attr    = is_numeric($rating_val) ? $pct : 0;

        $initialColor = score_to_hex($rating_val);
        $pathD = 'M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831';
        
        // Enhanced Icons with Glow effects
        switch ($id) {
            case 'overall':
                $icon = '<path d="M12 2l2.5 6.2L21 9l-4.5 3.2L17 19l-5-3.2L7 19l.5-6.8L3 9l6.5-.8L12 2z" fill="currentColor" />';
                $subtitle = 'Aggregated Score';
                $color_class = 'text-amber-400';
                $bg_class = 'bg-amber-400/10 group-hover:bg-amber-400/20';
                break;
            case 'attacking':
                $icon = '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>';
                $subtitle = 'Threat Creation';
                $color_class = 'text-rose-400';
                $bg_class = 'bg-rose-400/10 group-hover:bg-rose-400/20';
                break;
            case 'defense':
                $icon = '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>';
                $subtitle = 'Solidity & Structure';
                $color_class = 'text-emerald-400';
                $bg_class = 'bg-emerald-400/10 group-hover:bg-emerald-400/20';
                break;
            case 'distribution':
                $icon = '<circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2"/><circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49" stroke="currentColor" stroke-width="2"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49" stroke="currentColor" stroke-width="2"/>';
                $subtitle = 'Passing & Control';
                $color_class = 'text-blue-400';
                $bg_class = 'bg-blue-400/10 group-hover:bg-blue-400/20';
                break;
            case 'discipline':
                $icon = '<rect x="6" y="4" width="12" height="16" rx="2" stroke="currentColor" stroke-width="2"/><line x1="10" y1="8" x2="14" y2="8" stroke="currentColor" stroke-width="2"/>';
                $subtitle = 'Fouls & Cards';
                $color_class = 'text-orange-400';
                $bg_class = 'bg-orange-400/10 group-hover:bg-orange-400/20';
                break;
            default: // General
                $icon = '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2"/>';
                $subtitle = 'Match Metrics';
                $color_class = 'text-indigo-400';
                $bg_class = 'bg-indigo-400/10 group-hover:bg-indigo-400/20';
        }

        return "
        <div class=\"group flex flex-col bg-[#0f0f10] hover:bg-[#111113] border border-white/5 rounded-2xl transition-all duration-300 shadow-sm hover:shadow-2xl overflow-hidden\">
            
            <button id=\"{$btn_id}\"
                    type=\"button\"
                    aria-expanded=\"false\"
                    class=\"w-full relative flex items-center justify-between px-5 py-4 cursor-pointer outline-none active:scale-[0.99] transition-transform duration-200 hover:translate-y-[-2px] focus-visible:ring-2 focus-visible:ring-indigo-500/30\">
                
                <div class=\"flex items-center gap-4\">
                    <div class=\"w-12 h-12 rounded-xl flex items-center justify-center transition-colors duration-300 {$bg_class} {$color_class} shadow-sm border border-white/6\">
                        <svg class=\"w-6 h-6\" viewBox=\"0 0 24 24\" fill=\"none\" aria-hidden=\"true\">
                            {$icon}
                        </svg>
                    </div>

                    <div class=\"flex flex-col items-start\">
                        <span class=\"text-white text-lg font-semibold tracking-wide group-hover:text-indigo-200 transition-colors\">{$label}</span>
                        <span class=\"text-xs text-slate-500 font-medium uppercase tracking-wider\">{$subtitle}</span>
                    </div>
                </div>

                <div class=\"flex items-center gap-4\">
                    <div class=\"rating relative flex items-center justify-center\" 
                         data-rating=\"{$data_rating_attr}\" 
                         data-pct=\"{$data_pct_attr}\">
                        
                        <div class=\"relative w-14 h-14\">
                             <svg class=\"w-full h-full -rotate-90\" viewBox=\"0 0 36 36\">
                                <!-- muted track uses currentColor -->
                                <path class=\"text-slate-800\"
                                    d=\"{$pathD}\"
                                    fill=\"none\"
                                    stroke=\"currentColor\"
                                    stroke-width=\"2.5\" />

                                <!-- progress ring: color from inline CSS var set from PHP ($initialColor) -->
                                <path
                                    class=\"progress-ring\"
                                    d=\"{$pathD}\"
                                    fill=\"none\"
                                    stroke=\"{$initialColor}\"
                                    stroke-width=\"2.5\"
                                    stroke-linecap=\"round\"
                                    stroke-dasharray=\"100 100\"
                                    stroke-dashoffset=\"100\"
                                    style=\"--ring: {$initialColor}; stroke: var(--ring); filter: drop-shadow(0 0 6px var(--ring));\"
                                />
                            </svg>
                            <div class=\"absolute inset-0 flex items-center justify-center\">
                                <span class=\"rating-value text-sm font-bold text-white\">{$rating_display}</span>
                            </div>
                        </div>
                    </div>

                    <div class=\"caret-icon text-slate-500 transition-transform duration-300 transform group-aria-expanded:rotate-180\">
                        <svg class=\"w-5 h-5\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2.5\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19 9l-7 7-7-7\" />
                        </svg>
                    </div>
                </div>
            </button>

            <div id=\"{$wrapper_id}\" 
                 class=\"grid grid-rows-[0fr] transition-[grid-template-rows] duration-500 ease-[cubic-bezier(0.4,0,0.2,1)]\">
                <div class=\"overflow-hidden\">
                    <div id=\"{$container_id}\" 
                         data-insight-type=\"{$id}\"
                         class=\"px-6 pb-6 pt-2 text-slate-300 leading-relaxed text-[15px]\">
                         <div class=\"animate-pulse flex space-x-4\">
                            <div class=\"flex-1 space-y-3 py-1\">
                                <div class=\"h-2 bg-slate-700/50 rounded w-3/4\"></div>
                                <div class=\"h-2 bg-slate-700/50 rounded\"></div>
                                <div class=\"h-2 bg-slate-700/50 rounded w-5/6\"></div>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>";
    };

    echo $render_section('overall', 'Overall', 'overall');
    echo $render_section('general', 'General', 'general');
    echo $render_section('distribution', 'Distribution', 'distribution');
    echo $render_section('attacking', 'Attacking', 'attack');
    echo $render_section('defense', 'Defense', 'defense');
    echo $render_section('discipline', 'Discipline', 'discipline');
    ?>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const TYPES = ['overall','general','distribution','attacking','defense','discipline'];
    const BASE = '<?= site_url("reports/teamsummarycontroller/insight/") ?>';
    const matchId = document.getElementById('REPORT_MATCH_ID')?.value || '';
    const matchName = document.getElementById('REPORT_MATCH_NAME')?.value || '';

    const cache = {};

    const COLORS = {
        emerald: "#34d399",
        lime:    "#a3e635",
        yellow:  "#facc15",
        red:     "#f87171",
        gray:    "#94a3b8"
    };

    const PHRASE_COLOR_MAP = {
        "Outstanding performance": COLORS.emerald,
        "Strong performance": COLORS.lime,
        "Average, with room for improvement": COLORS.yellow,
        "Needs significant improvement": COLORS.red,
        "No prediction available": COLORS.gray
    };

    function getColorForScore(score) {
        if (score === null || score === '' || isNaN(score)) return COLORS.gray;
        const s = parseFloat(score);
        if (s >= 8.0) return COLORS.emerald;
        if (s >= 6.0) return COLORS.lime;
        if (s >= 4.0) return COLORS.yellow;
        return COLORS.red;
    }

    function getBtn(type) { return document.getElementById(type + '-btn'); }
    function getWrapper(type) { return document.getElementById('wrapper-' + type); }
    function getContainer(type) { return document.getElementById('summary-container-' + type); }

    // Robust: set both attribute + inline style + css var so browser paints immediately
    function updateRingColor(btnOrRingParent, colorHex) {
        if (!btnOrRingParent) return;
        // accept either the button element or the ring element itself
        const ring = (btnOrRingParent.classList && btnOrRingParent.classList.contains('progress-ring'))
            ? btnOrRingParent
            : btnOrRingParent.querySelector?.('.progress-ring');

        if (!ring) return;

        // 1) set CSS variable (works with server-side inline --ring and ensures var() resolves)
        ring.style.setProperty('--ring', colorHex);

        // 2) set stroke attribute + style to be extra robust
        try { ring.setAttribute('stroke', colorHex); } catch(e) {}
        ring.style.stroke = colorHex;

        // 3) set drop shadow
        ring.style.setProperty('filter', `drop-shadow(0 0 6px ${colorHex})`);

        // 4) ensure transitions are present
        ring.style.transition = ring.style.transition ? ring.style.transition + ', stroke 0.35s ease, filter 0.35s ease' : 'stroke 0.35s ease, filter 0.35s ease';
    }

    // Replace / refine ring offset using actual path length and animate
    function animateRatingRings() {
        // ensure we run after initial paint so inline styles from PHP are applied
        requestAnimationFrame(() => {
            document.querySelectorAll('.rating').forEach(el => {
                const pct = parseFloat(el.getAttribute('data-pct') || 0);
                const ratingValStr = el.getAttribute('data-rating');
                const ratingVal = parseFloat(ratingValStr);
                const btn = el.closest('button');

                // determine color from numeric score (fallbacks to gray)
                const color = getColorForScore(ratingValStr === '' ? null : ratingVal);

                // set color ASAP (applies CSS var + stroke attribute/style)
                updateRingColor(btn, color);

                // animate the progress arc
                const ring = el.querySelector('.progress-ring');
                if (ring) {
                    let circumference;
                    try {
                        // real path length (preferred)
                        circumference = Math.max(1, ring.getTotalLength());
                    } catch (err) {
                        circumference = 100; // fallback if path API not available
                    }

                    // set dasharray to circumference for precise animation
                    ring.style.strokeDasharray = `${circumference} ${circumference}`;

                    // start from full (so the animation is visible)
                    ring.style.strokeDashoffset = String(circumference);

                    // force layout so transitions apply
                    void ring.getBoundingClientRect();

                    // target offset based on pct
                    const targetOffset = circumference - (pct / 100) * circumference;

                    // set a transition explicitly (you can tweak duration)
                    ring.style.transition = 'stroke-dashoffset 900ms cubic-bezier(0.22, 1, 0.36, 1), stroke 350ms ease, filter 350ms ease';

                    // animate on next frame
                    requestAnimationFrame(() => {
                        ring.style.strokeDashoffset = String(targetOffset);
                    });
                }

                // animate number count-up
                const valEl = el.querySelector('.rating-value');
                if (!isNaN(ratingVal) && valEl) {
                    const end = ratingVal;
                    const duration = 1200;
                    const start = 0;
                    const startTime = performance.now();

                    function step(now) {
                        const elapsed = now - startTime;
                        const progress = Math.min(elapsed / duration, 1);
                        const ease = 1 - Math.pow(1 - progress, 4);
                        const current = start + (end - start) * ease;
                        valEl.textContent = current.toFixed(1);
                        if (progress < 1) requestAnimationFrame(step);
                        else valEl.textContent = end.toFixed(1);
                    }
                    requestAnimationFrame(step);
                }
            });
        });
    }

    function applyPerformanceColoring(container) {
        if (!container) return;
        let html = container.innerHTML;
        Object.entries({
            "Outstanding performance": "text-emerald-400 font-bold",
            "Strong performance": "text-lime-300 font-bold",
            "Average, with room for improvement": "text-yellow-300 font-bold",
            "Needs significant improvement": "text-red-400 font-bold",
            "No prediction available": "text-slate-400 font-medium"
        }).forEach(([phrase, cls]) => {
            if (html.includes(phrase)) {
                html = html.replace(new RegExp(phrase, 'g'), `<span class="${cls}">${phrase}</span>`);
            }
        });
        container.innerHTML = html;
    }

    function scanAndColorRing(type, htmlContent) {
        for (const [phrase, hex] of Object.entries(PHRASE_COLOR_MAP)) {
            if (htmlContent.includes(phrase)) {
                updateRingColor(getBtn(type), hex);
                break;
            }
        }
    }

    async function preloadColors() {
        await Promise.all(TYPES.map(async (type) => {
            if (cache[type]) return;
            try {
                let url = BASE + encodeURIComponent(type);
                const params = new URLSearchParams();
                if (matchId) params.append('match_id', matchId);
                if (matchName) params.append('match_name', matchName);
                const query = params.toString();
                if (query) url += '?' + query;

                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const rawHtml = await res.text();
                cache[type] = rawHtml;
                scanAndColorRing(type, rawHtml);
            } catch (err) {
                console.error('Preload error for ' + type + ':', err);
            }
        }));
    }

    async function loadInsightInto(type) {
        const container = getContainer(type);
        if (!container) return;

        if (cache[type]) {
            container.innerHTML = cache[type];
            applyPerformanceColoring(container);
            scanAndColorRing(type, container.innerHTML);
            return;
        }

        container.innerHTML = `
            <div class="animate-pulse flex space-x-4 max-w-lg">
                <div class="flex-1 space-y-3 py-1">
                    <div class="h-2 bg-slate-700/50 rounded w-3/4"></div>
                    <div class="h-2 bg-slate-700/50 rounded"></div>
                    <div class="h-2 bg-slate-700/50 rounded w-5/6"></div>
                </div>
            </div>`;

        try {
            let url = BASE + encodeURIComponent(type);
            const params = new URLSearchParams();
            if (matchId) params.append('match_id', matchId);
            if (matchName) params.append('match_name', matchName);
            const query = params.toString();
            if (query) url += '?' + query;

            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const rawHtml = await res.text();
            container.innerHTML = rawHtml;

            applyPerformanceColoring(container);
            scanAndColorRing(type, rawHtml);
            cache[type] = container.innerHTML;
        } catch (err) {
            console.error('Insight load error:', err);
            container.innerHTML = '<div class="text-sm text-rose-400">Unable to load insights.</div>';
        }
    }

    function closeAll(exceptType = null) {
        TYPES.forEach(t => {
            if (t === exceptType) return;
            const wrapper = getWrapper(t);
            const btn = getBtn(t);
            const caret = btn?.querySelector('.caret-icon');

            if (wrapper) {
                wrapper.classList.remove('grid-rows-[1fr]');
                wrapper.classList.add('grid-rows-[0fr]');
            }
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
                btn.classList.remove('bg-white/5');
            }
            if (caret) caret.classList.remove('rotate-180');
        });
    }

    function openSection(type) {
        const wrapper = getWrapper(type);
        const btn = getBtn(type);
        const caret = btn?.querySelector('.caret-icon');

        if (wrapper) {
            wrapper.classList.remove('grid-rows-[0fr]');
            wrapper.classList.add('grid-rows-[1fr]');
        }
        if (btn) {
            btn.setAttribute('aria-expanded', 'true');
            btn.classList.add('bg-white/5');
        }
        if (caret) caret.classList.add('rotate-180');
    }

    TYPES.forEach(type => {
        const btn = getBtn(type);
        const wrapper = getWrapper(type);
        if (btn && wrapper) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const isOpen = wrapper.classList.contains('grid-rows-[1fr]');
                if (isOpen) {
                    closeAll();
                } else {
                    closeAll(type);
                    openSection(type);
                    loadInsightInto(type);
                }
            });
        }
    });

    // run the initial coloring & animations on load
    animateRatingRings();
    preloadColors();
});
</script>