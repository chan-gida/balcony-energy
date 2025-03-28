<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('プロフィール') }}
        </h2>

        {{-- <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p> --}}
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- 居住地設定フォームを追加 -->
        <div>
            <x-input-label for="region_id" :value="__('居住地')" />
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <select id="prefecture" name="prefecture" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                        <option value="">都道府県を選択</option>
                        @foreach($regions->unique('prefecture_name') as $region)
                            <option value="{{ $region->prefecture_name }}" 
                                {{ $user->regions->first()?->prefecture_name === $region->prefecture_name ? 'selected' : '' }}>
                                {{ $region->prefecture_name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('prefecture')" />
                </div>
                <div>
                    <select id="region_id" name="region_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                        <option value="">市区町村を選択</option>
                        @if($user->regions->first())
                            @foreach($regions->where('prefecture_name', $user->regions->first()->prefecture_name) as $region)
                                <option value="{{ $region->id }}" {{ $user->regions->first()?->id === $region->id ? 'selected' : '' }}>
                                    {{ $region->town_name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('region_id')" />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('更新') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

@push('scripts')
<script>
document.getElementById('prefecture').addEventListener('change', function() {
    const prefecture = this.value;
    const regionSelect = document.getElementById('region_id');
    
    // 市区町村の選択をリセット
    regionSelect.innerHTML = '<option value="">市区町村を選択</option>';
    
    if (prefecture) {
        // 選択された都道府県に基づいて市区町村を取得
        fetch(`/api/regions/towns?prefecture=${encodeURIComponent(prefecture)}`)
            .then(response => response.json())
            .then(regions => {
                regions.forEach(region => {
                    const option = document.createElement('option');
                    option.value = region.id;
                    option.textContent = region.town_name;
                    regionSelect.appendChild(option);
                });
            });
    }
});
</script>
@endpush
