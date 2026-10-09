@if (Auth::user()->isManager())
    <button type="button" data-dropdown-toggle="dropdown-manager" class="justify-center items-center py-2 px-4 mr-2 text-sm font-medium dark:text-white bg-primary-700 rounded-lg sm:inline-flex hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 focus:outline-hidden">
        <span class="flex-1 ml-3 text-left whitespace-nowrap">            
            Lagerleitende
        </span>
        <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/> 
        </svg>
    </button>
    <div
        class="hidden z-50 my-4 w-56 text-base list-none navbar-background divide-y divide-gray-100 dark:bg-gray-700 dark:divide-gray-600 rounded-xl"
        id="dropdown-manager">
        <ul aria-labelledby="dropdown-manager" class="h-dropdown py-1 text-gray-700 dark:text-gray-300 overflow-y-auto" >
            <li>
                <a class="nav-link" href="{{route('dashboard.users.index')}}">Leiter</a>
            </li>
            <li>
                <a class="nav-link" href="{{route('dashboard.questions.index')}}">Fragen</a>
            </li>
            <li>
                <a class="nav-link" href="{{route('dashboard.camps.index')}}">Lager</a>
            </li>
        </ul>
    </div>
@endif
