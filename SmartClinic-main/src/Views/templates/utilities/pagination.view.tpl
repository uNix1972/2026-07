<style>
  .pagination-wrapper {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 1.2rem; flex-wrap: wrap; gap: 1rem;
  }
  .items-per-page select {
    border: 1px solid #ddd; border-radius: 10px;
    padding: 0.5rem 0.8rem; font-size: 0.9rem;
    background: #fff; outline: none; cursor: pointer;
    color: var(--cedro); font-weight: 700;
  }
  .items-per-page select:focus { border-color: var(--dorado); }
  .pagination-links { display: flex; align-items: center; gap: 0.4rem; }
  .pagination-links a {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; font-size: 0.9rem; font-weight: 700;
    color: var(--cedro); background: #fff;
    border: 1px solid #ddd; transition: all 0.2s;
  }
  .pagination-links a:hover { background: var(--dorado); color: #fff; border-color: var(--dorado); }
  .pagination-links a.active { background: var(--cedro); color: #fff; border-color: var(--cedro); }
  .pagination-links span {
    width: 36px; height: 36px;
    display: flex; align-items: center; justify-content: center;
    color: #999; font-size: 0.9rem;
  }
</style>

<div class="pagination-wrapper">
  <div class="items-per-page">
    <form action="{{url}}" method="get" id="pagination">
      <input type="hidden" name="page" value="{{page}}" />
      <select name="itemsPerPage" id="itemsPerPage">
        <option value="5" {{itemsPerPage_5}}>5 por página</option>
        <option value="10" {{itemsPerPage_10}}>10 por página</option>
        <option value="20" {{itemsPerPage_20}}>20 por página</option>
        <option value="50" {{itemsPerPage_50}}>50 por página</option>
      </select>
    </form>
  </div>
  <div class="pagination-links">
    {{foreach pages}}
      {{if url}}
        <a href="{{url}}" class="{{if active}}active{{endif active}}">{{page}}</a>
      {{endif url}}
      {{ifnot url}}
        <span>{{page}}</span>
      {{endifnot url}}
    {{endfor pages}}
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('itemsPerPage').addEventListener('change', function() {
      document.getElementById('pagination').submit();
    });
  });
</script>