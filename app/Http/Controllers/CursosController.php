<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CursosController;

class CursosController extends Controller
{
    public function index()
    {
        // Obteniendo los cursos de la base de datos
        $cursos = CursosController::obtenerCursos();
        
        return view('index', ['cursos' => $cursos]);

    }


    public function hill_climbing(Request $request)
    {
        // Recibiendo nombres de cursos - OK
        $nombres = $request->input('nombres');
        
        // Obteniendo los cursos de la base de datos - OK
        $cursos = CursosController::obtenerCursos();
        
        // Semana de clases - OK
        $semana = CursosController::obtenerSemana();
        
        // Número de iteraciones de la metaheurística
        $iteraciones = 200000;



        // PASO 1: GENERAR SOLUCIÓN ALEATORIA X
        

        // Curso que todos sus NRC se cruzan
        $cruzados = [];

        // NRC's elegidos
        $elegidos = [];

        // Aquí se están recorriendo los nombres de la lista recibida
        foreach ($nombres as $nombre) {

            // Aquí se están evaluando los NRC de cada nombre de curso
            foreach ($cursos[$nombre] as $nrc => $val1) {

                if ($nrc != 'campus' and $nrc != 'fecha_inicio' and $nrc != 'creditos') {

                    // NRC aceptado
                    $aceptado = false;

                    // Último NRC
                    $ultimo_nrc = CursosController::endKey($cursos[$nombre]);

                    // Último día del NRC
                    $ultimo_dia = CursosController::endKey($cursos[$nombre][$nrc]);
                    
                    // Aquí se están revisando todos los día de la semana de cada NRC
                    foreach ($cursos[$nombre][$nrc] as $dia => $val2) {
                        
                        if ($dia != 'materia' and $dia != 'curso' and $dia != 'seccion' and $dia != 'capacidad' and $dia != 'disponibles' and $dia != 'ocupados' and $dia != 'codigo_docente' and $dia != 'docente' and $dia != 'tipo') {
                            
                            $hora1 = $cursos[$nombre][$nrc][$dia]['hora1'];
                            $hora2 = $cursos[$nombre][$nrc][$dia]['hora2'];

                            if (empty($semana[$dia][$hora1]) and empty($semana[$dia][$hora2])) {
                                
                                $semana[$dia][$hora1] = $nrc;
                                $semana[$dia][$hora2] = $nrc;

                                if ($dia == $ultimo_dia) {
                                    $aceptado = true;
                                    $elegidos[] = $nrc;
                                    break;
                                }

                            } else {
                                
                                // Se quita el NRC de toda la semana
                                foreach ($semana as $day => $hours) {
                                    foreach ($hours as $hour => $time) {
                                        if ($time == $nrc) {
                                            $semana[$day][$hour] = '';
                                        }
                                    }
                                }

                                break;
                            }

                        }
                        
                    }
                    
                    if ($aceptado) {
                        break;
                    }

                    if ($nrc == $ultimo_nrc) {
                        $cruzados[] = $nombre;
                    }

                }

            }

        }
        
        // Devolver al estudiante a la selección de cursos si alguno se cruza
        if ($cruzados) 
        {
            $error = "Los NRC de los siguientes cursos se cruzan: ";

            $last = end($cruzados);
            foreach ($cruzados as $cruzado) {

                if ($last == $cruzado) {

                    $error .= $cruzado;

                } else {

                    $error .= $cruzado.", ";

                }

            }

            return redirect()->back()->with('error', $error);
        }
        else
        {

            //PASO 2: PERTURBAR X PARA OBTENER XP


            while ($iteraciones > 0) {

                // Creando una copia de la solución actual
                $perturbada = $semana;
                
                // Obteniendo dos NRC al azar del arrego $elegidos
                while (true) {

                    if (count($elegidos) == 1) 
                    {
                        $nrc1 = $elegidos[0];
                        break;
                    }
                    else
                    {
                        $nrc1 = array_rand(array_flip($elegidos));
                        $nrc2 = array_rand(array_flip($elegidos));
                        
                        if ($nrc1 != $nrc2) {
                            break;
                        }

                    }

                }

                // Obteniendo los nombres de los cursos del NRC 1 y NRC2
                foreach ($nombres as $nombre) {

                    foreach ($cursos[$nombre] as $nrc => $val1) {

                        if ($nrc != 'campus' and $nrc != 'fecha_inicio' and $nrc != 'creditos') {
                            
                            if ($nrc == $nrc1) 
                            {
                                $nombre1 = $nombre;
                            } 
                            elseif (isset($nrc2))
                            {
                                if ($nrc == $nrc2) 
                                {
                                    $nombre2 = $nombre;
                                }

                            }

                        }

                    }

                }
                
                // Se quita el NRC de toda la semana
                foreach ($perturbada as $day => $hours) {

                    foreach ($hours as $hour => $nrc) {
                        
                        if ($nrc == $nrc1) {

                            $perturbada[$day][$hour] = '';

                        }
                        elseif (isset($nrc2))
                        {
                            if ($nrc == $nrc2) 
                            {
                                $perturbada[$day][$hour] = '';
                            }
                            
                        } 

                    }

                }
                
                

            }
        }

    }

    
    public function obtenerDia($lun, $mar, $mie, $jue, $vie, $sab, $dom)
    {

        // Array donde vamos a guardar el nombre del día y las horas
        $dia = [];

        if($lun) {

            // Sustraemos las horas del texto de la celda
            $partes = explode('-', $lun);
            $hora1 = substr($partes[0], 0, 2);
            $hora2 = substr($partes[1], 0, 2);

            // Guardamos y luego devolvemos
            $dia = ['lunes', $hora1, $hora2];
            
        } elseif($mar) {

            // Sustraemos las horas del texto de la celda
            $partes = explode('-', $mar);
            $hora1 = substr($partes[0], 0, 2);
            $hora2 = substr($partes[1], 0, 2);

            // Guardamos y luego devolvemos
            $dia = ['martes', $hora1, $hora2];
            
        } elseif($mie) {

            // Sustraemos las horas del texto de la celda
            $partes = explode('-', $mie);
            $hora1 = substr($partes[0], 0, 2);
            $hora2 = substr($partes[1], 0, 2);

            // Guardamos y luego devolvemos
            $dia = ['miercoles', $hora1, $hora2];
            
        } elseif($jue) {

            // Sustraemos las horas del texto de la celda
            $partes = explode('-', $jue);
            $hora1 = substr($partes[0], 0, 2);
            $hora2 = substr($partes[1], 0, 2);

            // Guardamos y luego devolvemos
            $dia = ['jueves', $hora1, $hora2];
            
        } elseif($vie) {

            // Sustraemos las horas del texto de la celda
            $partes = explode('-', $vie);
            $hora1 = substr($partes[0], 0, 2);
            $hora2 = substr($partes[1], 0, 2);

            // Guardamos y luego devolvemos
            $dia = ['viernes', $hora1, $hora2];
            
        } elseif($sab) {

            // Sustraemos las horas del texto de la celda
            $partes = explode('-', $sab);
            $hora1 = substr($partes[0], 0, 2);
            $hora2 = substr($partes[1], 0, 2);

            // Guardamos y luego devolvemos
            $dia = ['sabado', $hora1, $hora2];
            
        } elseif($dom) {

            // Sustraemos las horas del texto de la celda
            $partes = explode('-', $dom);
            $hora1 = substr($partes[0], 0, 2);
            $hora2 = substr($partes[1], 0, 2);

            // Guardamos y luego devolvemos
            $dia = ['domingo', $hora1, $hora2];
            
        }

        return $dia;
    }


    public function obtenerCursos() 
    {
        // 1. Inicializamos el array donde se va a organizar la información
        $cursos = [];

        // 2. Capturamos todos los nombres de la base de datos sin repetir.
        $nombres = DB::select('select distinct Nombre_asignatura from cursos');
        
        foreach ($nombres as $nombre) {

            $nombre = $nombre->Nombre_asignatura;
            
            // 3. Obtenemos la primera fila de cada curso.
            $fila = DB::select('select * from cursos where Nombre_asignatura = "'.$nombre.'" limit 1');
            $fila = $fila[0];

            // 4. Guardamos la información en el array de cursos.
            $cursos[$nombre] = [
                'campus' => $fila->Campus,
                'fecha_inicio' => $fila->Fecha_inicio,
                'creditos' => $fila->Creditos
            ];

            // 5. Obtenemos todos los NRC del curso
            $lista_nrc = DB::select('select distinct Nrc from cursos where Nombre_asignatura = "'.$nombre.'"');
            
            // 6. Guardamos los NRC en el array de cursos
            foreach ($lista_nrc as $nrc) {

                $nrc = $nrc->Nrc;
                
                // 7. Obtenemos la primera fila de cada NRC.
                
                $info = DB::select('select * from cursos where Nombre_asignatura = "'.$nombre.'" and Nrc = "'.$nrc.'" limit 1');
                $info = $info[0];
                
                // 8. Guardamos la información en el array de cursos.
                $cursos[$nombre][$nrc] = [
                    'materia' => $info->Materia,
                    'curso' => $info->Curso,
                    'seccion' => $info->Seccion,
                    'capacidad' => $info->Capacidad,
                    'disponibles' => $info->Disponibles,
                    'ocupados' => $info->Ocupados,
                    'codigo_docente' => $info->Codigo_docente,
                    'docente' => $info->Docente,
                    'tipo' => $info->Tipo
                ];

                // 9. Obtenemos las filas de cada NRC.
                $datos_nrc = DB::select('select * from cursos where Nombre_asignatura = "'.$nombre.'" and Nrc = "'.$nrc.'"');
                
                foreach ($datos_nrc as $dato) {

                    // 10. Arreglamos el Hrs_sem para que no tenga \r al final.
                    $texto_malo = $dato->Hrs_sem;
                    $subcadena = substr($texto_malo, 0, 1);
                    $hrs_sem = intval($subcadena);

                    // 11. Obtenemos el día de la semana que tiene la hora de clase
                    $dia = CursosController::obtenerDia($dato->Lunes, $dato->Martes, $dato->Miercoles, $dato->Jueves, $dato->Viernes, $dato->Sabado, $dato->Domingo);
                    
                    // 12. Agregamos la información en el array de cursos
                    $cursos[$nombre][$nrc][$dia[0]] = [
                        'hora1' => $dia[1],
                        'hora2' => $dia[2],
                        'edificio' => $dato->Edf,
                        'salon' => $dato->Salon,
                        'semanales' => $hrs_sem
                    ];

                }

            }
            
        }

        return $cursos;

    }


    public function obtenerSemana()
    {
        $semana = [
            
            'lunes' => ['07' => '', '08' => '', '09' => '', '10' => '', '11' => '', '12' => '', '13' => '', '14' => '', '15' => '', '16' => '', '17' => '', '18' => '', '19' => '', '20' => ''],
            'martes' => ['07' => '', '08' => '', '09' => '', '10' => '', '11' => '', '12' => '', '13' => '', '14' => '', '15' => '', '16' => '', '17' => '', '18' => '', '19' => '', '20' => ''],
            'miercoles' => ['07' => '', '08' => '', '09' => '', '10' => '', '11' => '', '12' => '', '13' => '', '14' => '', '15' => '', '16' => '', '17' => '', '18' => '', '19' => '', '20' => ''],
            'jueves' => ['07' => '', '08' => '', '09' => '', '10' => '', '11' => '', '12' => '', '13' => '', '14' => '', '15' => '', '16' => '', '17' => '', '18' => '', '19' => '', '20' => ''],
            'viernes' => ['07' => '', '08' => '', '09' => '', '10' => '', '11' => '', '12' => '', '13' => '', '14' => '', '15' => '', '16' => '', '17' => '', '18' => '', '19' => '', '20' => ''],
            'sabado' => ['07' => '', '08' => '', '09' => '', '10' => '', '11' => '', '12' => '', '13' => '', '14' => '', '15' => '', '16' => '', '17' => '', '18' => '', '19' => '', '20' => ''],
            'domingo' => ['07' => '', '08' => '', '09' => '', '10' => '', '11' => '', '12' => '', '13' => '', '14' => '', '15' => '', '16' => '', '17' => '', '18' => '', '19' => '', '20' => '']
            
        ];

        return $semana;

    }


    // Returns the key at the end of the array
    function endKey( $array ){

        //Aquí utilizamos end() para poner el puntero
        //en el último elemento, no para devolver su valor
        end( $array );

        return key( $array );

    }
    

}