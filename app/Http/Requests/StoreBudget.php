<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Budget;

class StoreBudget extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true; // Change this if needed based on authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'budget_number'        => ['required', 'string', 'unique:budgets,budget_number'],
            'valid_till'           => ['required', 'date_format:D d M Y'],
            'currency_id'          => ['required', 'integer', 'exists:currencies,id'],
            'project_id'           => ['required', 'integer', 'exists:projects,id'],
            'item_name'            => ['required', 'array'],
            'quantity'             => ['required', 'array'],
            'cost_per_item'        => ['required', 'array'],
            'amount'               => ['required', 'array'],
            'sub_total'            => ['required', 'numeric'],
            'total'                => ['required', 'numeric'],
            'discount_value'       => ['nullable', 'numeric'],
            'discount_type'        => ['nullable', 'in:fixed,percent'],
            'budget_description'   => ['nullable', 'string'],
            'invoice_item_image'   => ['nullable', 'array'],
            'invoice_item_image.*' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif', 'max:2048'],
        ];
    }

    /**
     * Additional validation logic.
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $latestBudget = Budget::latest()->first();
            $budgetNumber = $this->input('budget_number');
            $project_id = $this->input('project_id');

            // Check if a budget already exists for the given project
            $projectBudgetExists = Budget::where('project_id', $project_id)->exists();

            if ($latestBudget && $budgetNumber && $budgetNumber <= $latestBudget->id) {
                $validator->errors()->add('budget_number', __('The budget number has already been taken.'));
            }

            if ($projectBudgetExists) {
                $validator->errors()->add('project_id', __('A budget already exists for this project.'));
            }
        });
    }


    /**
     * Custom error messages.
     */
    public function messages()
    {
        return [
            'budget_number.required' => 'The budget number is required.',
            'valid_till.required'    => 'The valid till date is required.',
            'valid_till.date_format' => 'The valid till date must be in "D d M Y" format (e.g., Mon 01 Jan 2025).',
            'currency_id.required'   => 'Currency selection is required.',
            'project_id.required'    => 'Project selection is required.',
            'item_name.required'     => 'At least one item is required in the budget.',
            'total.required'         => 'Total amount is required.',
            'invoice_item_image.*.image' => 'Uploaded files must be valid images (jpg, png, jpeg, gif).',
            'invoice_item_image.*.max'   => 'Each image must not exceed 2MB in size.',
        ];
    }
}
