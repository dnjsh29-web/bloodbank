@if (session('success'))
    <div class="toast fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-full bg-stone-900 px-6 py-3 text-sm font-extrabold text-white shadow-2xl" data-auto-dismiss="2000" data-turbo-temporary>{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
