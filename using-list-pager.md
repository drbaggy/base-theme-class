# List pager

```html
<div class="exhibits-wrapper list-container" data-key="exhibits" data-batch-size="9">
  <div class="list-inputs filter-wrapper">
    <div class="filter-container">
      <div class="filter-panel">
        <div class="filter-container">
          <div class="filter-label">
            <label for="n-0000-exhibition"><em>Exhibition</em></label>
          </div>
          <div class="filter-dd">
            <select id="n-0000-exhibition" class="list-filter filter" data-filter-type="array" data-filter="exhibition">
              <option value="" >All Exhibitions</option>
              <option value="a">Exhibition A (1)</option>
              <option value="b">Exhibition B (3)</option>
            </select>
          </div>
        </div>
        <div class="filter-panel">
          <div class="filter-container">
            <div class="filter-label">
              <label for="n-0000-type"><em>Type</em></label>
            </div>
            <div class="filter-dd">
              <select id="n-0000-type" class="list-filter filter" data-filter-type="array" data-filter="type">
                <option value="">All Types</option>
                <option value="a">Type A (1)</option>
                <option value="b">Type B (2)</option>
                <option value="c">Type C (1)</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="/wp-content/plugins/base-theme-class/list-pager.js" type="text/javascript"></script>
    <div class="card-wrapper">
      <div class="exhibit-container">
        <div class="exhibit-item list-item" data-exhibition="Exhibition 3" data-type="Digital image">
          Markup...
        </div>
        <div class="exhibit-item list-item" data-exhibition="Exhibition 2" data-type="Graphic">
          Markup...
        </div>
      </div>
    </div>
    <div class="no-matches">
      <p class="list-none" style="display: none;">There are no matches for your search criteria</p>
    </div>
    <div class="box-pagination">
      <input type="hidden" value="0" class="list-filter" data-filter="page" data-filter-type="page"/>
      <div class="pagination">&nbsp;</div>
    </div>
  </div>
</div>
```

## Pagination

## "View more"

## Setting up filtering

