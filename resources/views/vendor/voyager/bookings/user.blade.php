@php
    use App\Models\User;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    $currentRoute = Route::currentRouteName();
    $isBrowse = Str::contains($currentRoute, ['index', 'browse']);
    $isRead = Str::contains($currentRoute, ['show', 'read']);
    $isEditOrAdd = Str::contains($currentRoute, ['edit', 'create']);
    $item = $data ?? $dataTypeContent ?? null;
    $currentUserId = old('user_id') 
        ?? ($item->user_id ?? null);
    $currentUser = $currentUserId ? User::find($currentUserId) : null;
@endphp
<span>{{ $currentUser?->name ?? '—' }}</span>