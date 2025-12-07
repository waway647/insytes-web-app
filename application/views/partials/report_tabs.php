<?php
// Build query using values available in the scope (set by controllers)
$param_id = $match_id ?? $requested_match_id ?? $this->session->userdata('current_match_id') ?? '';
$param_name = $match_name ?? $requested_match_name ?? $this->session->userdata('current_match_name') ?? '';

$query = '';
if ($param_id !== '' || $param_name !== '') {
    $qs = [];
    if ($param_id !== '')  $qs['match_id']   = $param_id;
    if ($param_name !== '') $qs['match_name'] = $param_name;
    $query = '?' . http_build_query($qs);
}
?>

<div class="w-full flex">
    <a href="<?php echo site_url('reports/overviewcontroller/index') . $query; ?>"
        class="w-full flex justify-center items-center py-4 border-b-1 border-b-[#2a2a2a] cursor-pointer group hover:border-b-white transition-colors">
        <h1 class="text-[#B6BABD] group-hover:text-white">Overview</h1>
    </a>

    <a href="<?php echo site_url('reports/teamsummarycontroller/index') . $query; ?>"
        class="w-full flex justify-center items-center py-4 border-b-1 border-b-[#2a2a2a] cursor-pointer group hover:border-b-white transition-colors">
        <h1 class="text-[#B6BABD] group-hover:text-white">Team Summary</h1>
    </a>

    <a href="<?php echo site_url('reports/playerperformancecontroller/index') . $query; ?>"
        class="w-full flex justify-center items-center py-4 border-b-1 border-b-[#2a2a2a] cursor-pointer group hover:border-b-white transition-colors">
        <h1 class="text-[#B6BABD] group-hover:text-white">Player Performance</h1>
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  try {
    const tabs = document.querySelectorAll('.w-full.flex > a');
    const current = window.location.pathname + (window.location.search || '');
    // prefer matching controller segment - get last portion of path
    const path = window.location.pathname.toLowerCase();
    tabs.forEach(a => {
      const href = a.getAttribute('href') || '';
      // compare by path segment (strip origin)
      const relative = href.replace(window.location.origin, '').toLowerCase();
      // simple heuristics: check if the href path is contained in current path OR the other way round
      if (relative && (current.toLowerCase().includes(relative) || relative.includes(path))) {
        a.classList.add('border-b-white');
        const h = a.querySelector('h1');
        if (h) { h.classList.remove('text-[#B6BABD]'); h.classList.add('text-white'); }
      } else {
        a.classList.remove('border-b-white');
      }
    });
  } catch (e) { console.warn('tab active script error', e); }
});
</script>
