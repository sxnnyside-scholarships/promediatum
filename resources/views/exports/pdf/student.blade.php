@extends('exports.pdf.layout')

@section('content')
    <div class="page-header">
        @if($config['include_header_text'])
            <div class="custom-header">{{ $config['include_header_text'] }}</div>
        @endif
        <h1>Reporte Individual de Estudiante</h1>
        <div class="subtitle">{{ $data['student']['full_name'] }}</div>
    </div>

    <div class="meta-bar">
        <div class="left">{{ $data['group']['name'] }} — {{ $data['group']['subject'] ?? '' }}</div>
        <div class="right">Periodo: {{ $data['period']['name'] }}</div>
    </div>

    {{-- Context box --}}
    <div class="context-box">
        <h3>Información General</h3>
        <div class="context-grid">
            <div class="row">
                <div class="label">Estudiante:</div>
                <div class="value">{{ $data['student']['full_name'] }}</div>
            </div>
            <div class="row">
                <div class="label">Grupo:</div>
                <div class="value">{{ $data['group']['name'] }}</div>
            </div>
            <div class="row">
                <div class="label">Materia:</div>
                <div class="value">{{ $data['group']['subject'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Periodo:</div>
                <div class="value">{{ $data['period']['name'] }} ({{ $data['period']['start_date'] }} – {{ $data['period']['end_date'] }})</div>
            </div>
            <div class="row">
                <div class="label">Promedio Ponderado:</div>
                <div class="value">
                    <strong>
                        {{ $data['academic']['weighted_average'] !== null
                            ? number_format($data['academic']['weighted_average'], $config['numeric_precision'])
                            : '—' }}
                    </strong>
                </div>
            </div>
            <div class="row">
                <div class="label">Estado:</div>
                <div class="value">
                    @if($data['academic']['at_risk'])
                        <span class="badge badge-danger">En riesgo</span>
                    @else
                        <span class="badge badge-success">Normal</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Attendance summary --}}
    @if($config['include_attendance_summary'])
        <div class="section">
            <h2>Asistencia</h2>
            <table class="compact-table">
                <thead>
                    <tr>
                        <th>Presentes</th>
                        <th>Faltas</th>
                        <th>Justificadas</th>
                        <th>Total Sesiones</th>
                        <th>Tasa Asistencia</th>
                        <th>Racha Ausencias</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $data['attendance']['present'] }}</td>
                        <td>{{ $data['attendance']['absent'] }}</td>
                        <td>{{ $data['attendance']['justified'] }}</td>
                        <td>{{ $data['attendance']['total'] }}</td>
                        <td>{{ $data['attendance']['rate'] !== null ? $data['attendance']['rate'] . '%' : '—' }}</td>
                        <td>{{ $data['academic']['absence_streak'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    {{-- Category breakdown --}}
    @if($config['include_category_breakdown'] && !empty($data['categories']))
        <div class="section">
            <h2>Desglose por Categoría</h2>
            @foreach($data['categories'] as $category)
                <h3 style="font-size: 11px; color: #78350f; margin: 10px 0 6px;">
                    {{ $category['name'] }} — Peso: {{ $category['weight'] }}% — Promedio: {{ number_format($category['average'], $config['numeric_precision']) }}
                </h3>
                @if(!empty($category['grades']))
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Evaluación</th>
                                <th class="number">Puntaje</th>
                                <th class="number">Máximo</th>
                                <th class="number">Porcentaje</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category['grades'] as $grade)
                                <tr>
                                    <td>{{ $grade['title'] }}</td>
                                    <td class="number">{{ number_format($grade['score'], $config['numeric_precision']) }}</td>
                                    <td class="number">{{ number_format($grade['max_score'], $config['numeric_precision']) }}</td>
                                    <td class="number">{{ $grade['percentage'] }}%</td>
                                    <td>{{ $grade['date'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: #78716c; font-size: 10px;">Sin evaluaciones registradas.</p>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Observations --}}
    @if($config['include_observations_summary'] && !empty($data['observations']))
        <div class="section">
            <h2>Observaciones</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Contenido</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['observations'] as $obs)
                        <tr>
                            <td>{{ ucfirst($obs['type']) }}</td>
                            <td>{{ $obs['content'] }}</td>
                            <td>{{ ucfirst($obs['status']) }}</td>
                            <td>{{ $obs['created_at'] }}</td>
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
