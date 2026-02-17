@extends('exports.pdf.layout')

@section('content')
    <div class="page-header">
        @if($config['include_header_text'])
            <div class="custom-header">{{ $config['include_header_text'] }}</div>
        @endif
        <h1>Reporte de Grupo</h1>
        <div class="subtitle">{{ $data['group']['name'] }} — {{ $data['group']['subject'] ?? '' }}</div>
    </div>

    <div class="meta-bar">
        <div class="left">Periodo: {{ $data['period']['name'] }} ({{ $data['period']['start_date'] }} – {{ $data['period']['end_date'] }})</div>
        <div class="right">Nivel: {{ $data['group']['educational_level'] ?? 'N/A' }}</div>
    </div>

    {{-- Summary cards --}}
    <div class="summary-cards">
        <div class="summary-card">
            <div class="value">{{ count($data['students']) }}</div>
            <div class="label">Estudiantes</div>
        </div>
        <div class="summary-card">
            <div class="value">{{ count($data['categories']) }}</div>
            <div class="label">Categorías</div>
        </div>
        <div class="summary-card">
            @php
                $avgAll = collect($data['students'])->pluck('weighted_average')->filter()->avg();
            @endphp
            <div class="value">{{ $avgAll !== null ? number_format($avgAll, $config['numeric_precision']) : '—' }}</div>
            <div class="label">Promedio General</div>
        </div>
        <div class="summary-card">
            @php
                $riskCount = collect($data['students'])->where('at_risk', true)->count();
            @endphp
            <div class="value">{{ $riskCount }}</div>
            <div class="label">En Riesgo</div>
        </div>
    </div>

    {{-- Student data table --}}
    <div class="section">
        <h2>Listado de Estudiantes</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Estudiante</th>
                    <th class="number">Promedio</th>
                    <th class="number">Asistencia</th>
                    <th class="number">Presentes</th>
                    <th class="number">Faltas</th>
                    <th class="number">Justificadas</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['students'] as $index => $student)
                    <tr class="{{ $student['at_risk'] ? 'at-risk' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $student['full_name'] }}</td>
                        <td class="number">
                            {{ $student['weighted_average'] !== null
                                ? number_format($student['weighted_average'], $config['numeric_precision'])
                                : '—' }}
                        </td>
                        <td class="number">
                            {{ $student['attendance_rate'] !== null ? $student['attendance_rate'] . '%' : '—' }}
                        </td>
                        <td class="number">{{ $student['present'] }}</td>
                        <td class="number">{{ $student['absent'] }}</td>
                        <td class="number">{{ $student['justified'] }}</td>
                        <td>
                            @if($student['at_risk'])
                                <span class="badge badge-danger">En riesgo</span>
                            @else
                                <span class="badge badge-success">Normal</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Category breakdown --}}
    @if($config['include_category_breakdown'] && !empty($data['categories']))
        <div class="section">
            <h2>Categorías de Evaluación</h2>
            <table class="compact-table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th class="number">Peso (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['categories'] as $category)
                        <tr>
                            <td>{{ $category['name'] }}</td>
                            <td class="number">{{ $category['weight'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Signature --}}
    @if($config['include_signature_line'])
        <div class="signature-section">
            <div class="signature-line">Firma del Docente</div>
        </div>
    @endif
@endsection
