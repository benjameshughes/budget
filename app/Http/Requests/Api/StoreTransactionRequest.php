<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth handled by Sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'date' => ['nullable', 'date'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', auth()->id())],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The transaction name is required.',
            'amount.required' => 'The amount is required.',
            'amount.min' => 'The amount must be at least 0.01.',
            'type.required' => 'The transaction type is required.',
            'type.enum' => 'The transaction type must be either "income" or "expense".',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }
}
