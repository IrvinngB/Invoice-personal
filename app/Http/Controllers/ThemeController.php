<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use App\Models\InvoiceTheme;
use App\Services\PdfService;
use App\Services\SampleInvoiceFactory;
use App\Services\ThemeTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ThemeController extends Controller
{
    public function index()
    {
        return view('settings.themes.index', [
            'presets' => PdfService::SUPPORTED_TEMPLATES,
            'themes' => InvoiceTheme::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request, PdfService $pdfService)
    {
        $base = $this->baseFrom($request->query('base'));

        return view('settings.themes.edit', [
            'theme' => new InvoiceTheme(['name' => '', 'base' => $base, 'overrides' => []]),
            'values' => $this->formValues($pdfService, $base, []),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $theme = InvoiceTheme::create($data);

        return redirect()->route('settings.themes.edit', $theme)->with('success', __('app.theme_saved'));
    }

    public function edit(InvoiceTheme $theme, PdfService $pdfService)
    {
        return view('settings.themes.edit', [
            'theme' => $theme,
            'values' => $this->formValues($pdfService, $theme->base, $theme->overrides ?? []),
        ]);
    }

    public function update(Request $request, InvoiceTheme $theme)
    {
        $data = $this->validated($request);
        unset($data['base']); // the base preset is fixed once created
        $theme->update($data);

        return redirect()->route('settings.themes.edit', $theme)->with('success', __('app.theme_saved'));
    }

    public function destroy(InvoiceTheme $theme)
    {
        DB::transaction(function () use ($theme) {
            // Anything still pointing at this theme falls back to its base preset.
            Invoice::where('pdf_template', $theme->key())->update(['pdf_template' => $theme->base]);
            BusinessProfile::where('default_template', $theme->key())->update(['default_template' => $theme->base]);
            $theme->delete();
        });

        return redirect()->route('settings.themes.index')->with('success', __('app.theme_deleted'));
    }

    /**
     * Inline PDF of a sample invoice using the (unsaved) tokens in the query string.
     */
    public function preview(Request $request, PdfService $pdfService)
    {
        $base = $this->baseFrom($request->query('base'));
        $locale = in_array($request->query('locale'), ['en', 'es'], true) ? $request->query('locale') : app()->getLocale();

        $profile = BusinessProfile::current();
        $query = $request->query();
        $query['labels'] = ThemeTokens::labelsFromForm(
            is_array($query['labels'] ?? null) ? $query['labels'] : [],
            is_array($query['labels_blank'] ?? null) ? $query['labels_blank'] : [],
        );
        $tokens = $pdfService->previewTheme($base, $query);
        $pdf = $pdfService->generate(SampleInvoiceFactory::make($profile, $locale), $profile, $base, $tokens);

        return $pdf->stream('theme-preview.pdf');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'base' => ['required', Rule::in(PdfService::SUPPORTED_TEMPLATES)],
        ] + ThemeTokens::rules());

        return [
            'name' => $validated['name'],
            'base' => $validated['base'],
            // Unchecked checkboxes are absent from the request, so default the toggle explicitly.
            'overrides' => ThemeTokens::sanitize(array_merge($validated, [
                'uppercase_labels' => $request->boolean('uppercase_labels'),
                'labels' => ThemeTokens::labelsFromForm($validated['labels'] ?? [], $validated['labels_blank'] ?? []),
            ])),
        ];
    }

    private function baseFrom(mixed $base): string
    {
        return in_array($base, PdfService::SUPPORTED_TEMPLATES, true) ? $base : 'modern';
    }

    /**
     * Effective token values (preset + overrides) shaped for the editor form.
     */
    private function formValues(PdfService $pdfService, string $base, array $overrides): array
    {
        $theme = $pdfService->previewTheme($base, $overrides);
        $sanitized = ThemeTokens::sanitize($overrides);

        return [
            'accent' => $theme['accent'],
            'font' => $theme['font'],
            'header' => $theme['header'],
            'table' => $theme['table'],
            'total' => $theme['total'],
            'radius' => $theme['radius'],
            'scale' => $sanitized['scale'] ?? 'normal',
            'uppercase_labels' => $theme['uppercase_labels'],
            'show' => $theme['show'],
            // Editor state: custom text per label, and which ones are blanked out.
            'labels' => collect(ThemeTokens::labelKeys())->mapWithKeys(fn ($k) => [$k => ($theme['labels'][$k] ?? '')])->all(),
            'labels_blank' => collect(ThemeTokens::labelKeys())->mapWithKeys(fn ($k) => [$k => (($theme['labels'][$k] ?? null) === '')])->all(),
        ];
    }
}
