{{-- <div id="filter_btn">
    <div>
        <button class="btn btn-primary active" value="master">Nur Übergeordnete</button>
    </div>
    <div>
        <button class="btn btn-primary" value="all">Alle</button>
    </div>
</div> --}}
<div class="inline-flex rounded-md shadow-xs" role="group" id="filter_btn">
    <button type="button" value="all" class="btn__filter px-4 py-2 text-sm font-medium text-gray-900 bg-slate-50 white border border-gray-200 rounded-s-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
        Alle
    </button>
    <button type="button" value="master" class="active btn__filter px-4 py-2 text-sm font-medium text-gray-900 bg-slate-50 border border-gray-200 rounded-e-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-600 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
        Nur Übergeordnete
    </button>
  </div>
  <input type="hidden" value="master" id="btn_value">