@extends('exports.pdf.layout')

@section('content')
    <div class="page-header">
        @if($config['include_header_text'])
            <div class="custom-header">{{ $config['include_header_text'] }}</div>
        @endif
        <h1>Reporte de Periodo</h1>
        <div class="subtitle">{{ $data['period']['name'] }} ({{ $data['period']['start_date'] }} – {{ $data['period']['end_date'] }})</div>
    </div>

    <div class="meta-bar">
        <div class="left">Estado: {{ $data['period']['is_active'] ? 'Activo' : 'Finalizado' }}</div>
        <div class="right">Generado: {{ $meta['generated_at'] }}</div>
    </div>

    {{-- Summary cards --}}
    <div class="summary-cards">
        <div class="summary-card">
            <div class="value">{{ $data['summary']['total_groups'] }}</div>
            <div class="label">Grupos</div>
        </div>
        <div class="summary-card">
            <div class="value">{{ $data['summary']['total_students'] }}</div>
            <div class="label">Estudiantes</div>
        </div>
        <div class="summary-card">
            <div class="value">{{ $data['summary']['total_at_risk'] }}</div>
            <div class="label">En Riesgo</div>
        </div>
    </div>

    {{-- Groups table --}}
    <div class="section">
        <h2>Resumen por Grupo</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Materia</th>
                    <th>Nivel</th>
                    <th class="number">Estudiantes</th>
                    <th class="number">Promedio</th>
                    <th class="number">Asistencia</th>
                    <th class="number">En Riesgo</th>
                    <th class="number">Categorías</th>
                    <th class="number">Peso Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['groups'] as $group)
                    <tr>
                        <td>{{ $group['group_name'] }}</td>
                        <td>{{ $group['subject'] ?? '' }}</td>
                        <td>{{ $group['educational_level'] ?? '' }}</td>
                        <td class="number">{{ $group['students_count'] }}</td>
                        <td class="number">
                            {{ $group['group_average'] !== null
                                ? number_format($group['group_average'], $config['numeric_precision'])
                                : '—' }}
                        </td>
                        <td class="number">
                            {{ $group['group_attendance'] !== null ? $group['group_attendance'] . '%' : '—' }}
                        </td>
                        <td class="number">{{ $group['students_at_risk'] }}</td>
                        <td class="number">{{ $group['categories_count'] }}</td>
                        <td class="number">{{ $group['total_weight'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Signature --}}
    @if($config['include_signature_line'])
        <div class="signature-section">
            <div class="signature-line">Firma del Docente</div>
        </div>
    @endif
@endsection
