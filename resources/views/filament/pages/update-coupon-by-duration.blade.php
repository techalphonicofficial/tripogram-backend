<x-filament::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
        
        {{-- Show count of packages that will be updated --}}
        @if($duration && $packageCount !== null)
            <div class="mt-4 p-4 rounded-lg" 
                 style="background-color: {{ $packageCount > 0 ? '#e8f5e9' : '#ffebee' }}; border: 1px solid {{ $packageCount > 0 ? '#4caf50' : '#f44336' }};">
                <div class="flex items-center gap-3">
                    <div class="text-2xl">
                        @if($packageCount > 0)
                            📦
                        @else
                            ⚠️
                        @endif
                    </div>
                    <div>
                        @if($packageCount > 0)
                            <p class="font-semibold text-green-800">
                                {{ $packageCount }} Package(s) Found
                            </p>
                            <p class="text-sm text-green-600">
                                Coupon amount of {{ $coupon_amount ?? '0' }} will be applied to all
                            </p>
                        @else
                            <p class="font-semibold text-red-800">
                                No packages found for duration "{{ $duration }}"
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-4">
            <x-filament::button type="submit" :disabled="$packageCount === 0">
                Update Coupon
            </x-filament::button>
        </div>
    </form>

    {{-- Show current coupons summary --}}
    <div class="mt-8">
        <h3 class="text-lg font-semibold mb-3">📋 Current Coupons Summary</h3>
        
        @php
            $currentCoupons = App\Models\Packages::select('duration', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'), 'coupon_amount')
                ->groupBy('duration', 'coupon_amount')
                ->orderBy('duration')
                ->get();
        @endphp
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-2 text-left">Duration</th>
                        <th class="border p-2 text-left">Total Packages</th>
                        <th class="border p-2 text-left">Current Coupon Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($currentCoupons as $coupon)
                        <tr class="hover:bg-gray-50">
                            <td class="border p-2">{{ $coupon->duration }}</td>
                            <td class="border p-2">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">
                                    {{ $coupon->total }}
                                </span>
                            </td>
                            <td class="border p-2">
                                @if($coupon->coupon_amount)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded">
                                        ₹{{ number_format($coupon->coupon_amount, 2) }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded">
                                        No Coupon
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="border p-2 text-center text-gray-500">
                                No packages found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>