import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Consumable, Option, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    consumables: Paginated<Consumable>;
    filters: { search?: string; category?: string; stock?: string };
    categories: Option[];
    canManage: boolean;
}

function stockColor(status: string): string {
    switch (status) {
        case 'empty':
            return 'red';
        case 'low':
            return 'orange';
        case 'ok':
            return 'green';
        default:
            return 'gray';
    }
}

export default function Index({
    consumables,
    filters,
    categories,
    canManage,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [category, setCategory] = useState(filters.category ?? '');
    const [stock, setStock] = useState(filters.stock ?? '');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('consumables.index'),
            {
                search: search || undefined,
                category: category || undefined,
                stock: stock || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const reset = () => {
        setSearch('');
        setCategory('');
        setStock('');
        router.get(route('consumables.index'), {}, { preserveState: true });
    };

    const adjust = (id: number, delta: number) => {
        router.post(
            route('consumables.adjust', id),
            { delta },
            { preserveScroll: true },
        );
    };

    const destroy = (item: Consumable) => {
        if (confirm(`Supprimer « ${item.name} » ?`)) {
            router.delete(route('consumables.destroy', item.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Consommables"
                    description="Stocks, seuils et réapprovisionnements."
                    actions={
                        canManage ? (
                            <Link
                                href={route('consumables.create')}
                                className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                            >
                                Nouveau consommable
                            </Link>
                        ) : undefined
                    }
                />
            }
        >
            <Head title="Consommables" />

            <div className="mx-auto max-w-6xl space-y-6">
                <FilterBar onSubmit={submit} onReset={reset}>
                    <TextInput
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Nom, SKU…"
                        className="w-full min-w-48"
                    />
                    <select
                        value={category}
                        onChange={(e) => setCategory(e.target.value)}
                        className="ui-input min-w-40"
                    >
                        <option value="">Toutes catégories</option>
                        {categories.map((opt) => (
                            <option key={opt.value} value={opt.value}>
                                {opt.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={stock}
                        onChange={(e) => setStock(e.target.value)}
                        className="ui-input min-w-36"
                    >
                        <option value="">Tous stocks</option>
                        <option value="low">Stock bas / rupture</option>
                    </select>
                </FilterBar>

                <Surface padding={false} className="overflow-hidden">
                    {consumables.data.length === 0 ? (
                        <EmptyState
                            title="Aucun consommable"
                            description="Ajoutez toner, câbles et autres stocks du parc."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line">
                                <thead className="bg-surface-muted">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Article
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Catégorie
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Stock
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Lieu
                                        </th>
                                        {canManage && (
                                            <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                                Actions
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line bg-surface">
                                    {consumables.data.map((item) => (
                                        <tr
                                            key={item.id}
                                            className="hover:bg-surface-muted/60"
                                        >
                                            <td className="px-6 py-4">
                                                <div className="font-medium text-ink">
                                                    {item.name}
                                                </div>
                                                <div className="text-xs text-ink-muted">
                                                    {item.sku ?? '—'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {item.category_label}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Badge
                                                        label={item.stock_label}
                                                        color={stockColor(
                                                            item.stock_status,
                                                        )}
                                                    />
                                                    <span className="text-sm text-ink">
                                                        {item.quantity}{' '}
                                                        {item.unit}
                                                    </span>
                                                    {canManage && (
                                                        <span className="inline-flex gap-1">
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    adjust(
                                                                        item.id,
                                                                        -1,
                                                                    )
                                                                }
                                                                className="rounded border border-line px-2 py-0.5 text-xs font-semibold text-ink hover:bg-surface-muted ui-focus"
                                                            >
                                                                −1
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    adjust(
                                                                        item.id,
                                                                        1,
                                                                    )
                                                                }
                                                                className="rounded border border-line px-2 py-0.5 text-xs font-semibold text-ink hover:bg-surface-muted ui-focus"
                                                            >
                                                                +1
                                                            </button>
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {item.location?.name ?? '—'}
                                            </td>
                                            {canManage && (
                                                <td className="px-6 py-4 text-right text-sm">
                                                    <Link
                                                        href={route(
                                                            'consumables.edit',
                                                            item.id,
                                                        )}
                                                        className="me-3 font-medium text-brand hover:text-brand-strong"
                                                    >
                                                        Modifier
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            destroy(item)
                                                        }
                                                        className="font-medium text-danger hover:text-red-800"
                                                    >
                                                        Supprimer
                                                    </button>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination links={consumables.links} />
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
