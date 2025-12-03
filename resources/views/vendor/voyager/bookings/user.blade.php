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
@if($isBrowse || $isRead)
    <span>{{ $currentUser?->name ?? '—' }}</span>
@elseif($isEditOrAdd)
    @php($users = User::all(['id', 'name']))
    <select class="form-control select2" name="user_id" id="user_id">
        <option value="">Выберите пользователя</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ $user->id == $currentUserId ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
@endif