<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $customers = Customer::query()
            ->when($request->string('search')->trim()->value(), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->query('sort') === 'name',
                fn ($query) => $query->orderBy('name'),
                fn ($query) => $query->latest('id'),
            )
            ->paginate(max(1, min($request->integer('per_page', 25), 100)));

        return CustomerResource::collection($customers);
    }

    public function store(CustomerRequest $request): CustomerResource
    {
        return new CustomerResource(Customer::create($request->validated()));
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }

    public function update(CustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer): Response
    {
        $customer->delete();

        return response()->noContent();
    }
}
