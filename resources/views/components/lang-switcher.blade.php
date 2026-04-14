@php $lc = app()->getLocale(); @endphp
<div class="flex items-center gap-1">
    <a href="{{ route('locale.switch', 'zh_TW') }}"
   class="rounded-lg border px-2 py-1 text-xs font-semibold transition {{ $lc === 'zh_TW' ? 'border-orange-400 bg-orange-50 text-orange-700' : 'border-slate-200 text-slate-500 hover:bg-slate-50' }}">ZH</a>
    <a href="{{ route('locale.switch', 'en') }}"
       class="rounded-lg border px-2 py-1 text-xs font-semibold transition {{ $lc === 'en' ? 'border-orange-400 bg-orange-50 text-orange-700' : 'border-slate-200 text-slate-500 hover:bg-slate-50' }}">EN</a>
    <a href="{{ route('locale.switch', 'vi') }}"
       class="rounded-lg border px-2 py-1 text-xs font-semibold transition {{ $lc === 'vi' ? 'border-orange-400 bg-orange-50 text-orange-700' : 'border-slate-200 text-slate-500 hover:bg-slate-50' }}">VI</a>
</div>
