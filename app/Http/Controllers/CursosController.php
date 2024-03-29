<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CursosController;
use PhpParser\Node\Stmt\Foreach_;

class CursosController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
	}

	public function index()
	{

		$cursos = CursosController::obtenerCursos();
		$n = (float)rand() / (float)getrandmax();
		$n = (int)round($n * 3);

		while ($n != 1 and $n != 2 and $n != 3) {
			$n = (float)rand() / (float)getrandmax();
			$n = (int)round($n * 3);
		}

		$n = 2;

		switch ($n) {
			case 1:
				$meta = 'hill_climbing';
				break;

			case 2:
				$meta = 'simulated_annealing';
				break;

			case 3:
				$meta = 'ant_colony';
				break;
		}

		return view('home', ['cursos' => $cursos, 'n' => $n, 'meta' => $meta]);
	}

	public function obtenerCursos()
	{

		$cursos = [];
		$nombres = DB::select("select distinct Nombre_asignatura from cursos");

		foreach ($nombres as $nombre) {

			$nombre = $nombre->Nombre_asignatura;
			$fila = DB::select("select * from cursos where Nombre_asignatura = '$nombre' limit 1")[0];

			$cursos[$nombre] = [
				'campus' => $fila->Campus,
				'fecha_inicio' => $fila->Fecha_inicio,
				'creditos' => $fila->Creditos,
				'nrc' => []
			];

			$lista_nrc = DB::select("select distinct Nrc from cursos where Nombre_asignatura = '$nombre'");

			foreach ($lista_nrc as $nrc) {

				$nrc = $nrc->Nrc;
				$datos_nrc = DB::select("select * from cursos where Nombre_asignatura = '$nombre' and Nrc = '$nrc'");
				$info = $datos_nrc[0];
				$cursos[$nombre]['nrc'][$nrc] = [
					'materia' => $info->Materia,
					'curso' => $info->Curso,
					'seccion' => $info->Seccion,
					'capacidad' => $info->Capacidad,
					'disponibles' => $info->Disponibles,
					'ocupados' => $info->Ocupados,
					'codigo_docente' => $info->Codigo_docente,
					'docente' => $info->Docente,
					'tipo' => $info->Tipo,
					'dias' => []
				];

				foreach ($datos_nrc as $dato) {

					$texto_malo = $dato->Hrs_sem;
					$subcadena = substr($texto_malo, 0, 1);
					$hrs_sem = intval($subcadena);

					$fechas['lunes'] = $dato->Lunes;
					$fechas['martes'] = $dato->Martes;
					$fechas['miercoles'] = $dato->Miercoles;
					$fechas['jueves'] = $dato->Jueves;
					$fechas['viernes'] = $dato->Viernes;
					$fechas['sabado'] = $dato->Sabado;
					$fechas['domingo'] = $dato->Domingo;

					$dias = CursosController::obtenerDia($fechas);

					foreach ($dias as $i => $val) {

						if (isset($cursos[$nombre]['nrc'][$nrc]['dias'][$dias[$i][0]])) {
							$cursos[$nombre]['nrc'][$nrc]['dias'][$dias[$i][0]]['horas'][] = $dias[$i][1];
							$cursos[$nombre]['nrc'][$nrc]['dias'][$dias[$i][0]]['horas'][] = $dias[$i][2];
						} else {
							$cursos[$nombre]['nrc'][$nrc]['dias'][$dias[$i][0]] = [
								'horas' => [$dias[$i][1], $dias[$i][2]],
								'edificio' => $dato->Edf,
								'salon' => $dato->Salon,
								'semanales' => $hrs_sem
							];
						}
					}
				}
			}
		}

		return $cursos;
	}

	public function obtenerDia($fechas)
	{
		$dias = [];

		foreach ($fechas as $dia => $horas_unidas) {
			if ($horas_unidas) {
				$horas = CursosController::romperHoras($horas_unidas);
				$dias[] = [$dia, $horas['hora1'], $horas['hora2']];
			}
		}

		return $dias;
	}

	public function romperHoras($dia)
	{

		$partes = explode('-', $dia);
		$parte1 = substr($partes[0], 0, 2);

		if ($parte1[0] == 0) {
			$hora1 = $parte1[1];
		} else {
			$hora1 = $parte1;
		}

		$parte2 = substr($partes[1], 0, 2);

		if ($parte2[0] == 0) {
			$hora2 = $parte2[1];
		} else {
			$hora2 = $parte2;
		}

		$horas['hora1'] = $hora1;
		$horas['hora2'] = $hora2;

		return $horas;
	}

	public function obtenerSemana()
	{
		for ($i = 7; $i <= 20; $i++) {
			$horas[$i] = '';
		}

		$dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

		foreach ($dias as $dia) {
			$semana[$dia] = $horas;
		}

		return $semana;
	}

	public function endKey($array, $num)
	{
		if ($num == 1) {

			$array2 = $array;

			while (true) {

				end($array2);
				$llave = key($array2);
				$seccion = DB::select("select Seccion from cursos where Nrc = '" . $llave . "'")[0]->Seccion;

				if (substr($seccion, -1) == "1" or substr($seccion, -1) == "2") {
					array_pop($array2);
					continue;
				} else {
					break;
				}
			}
		} elseif ($num == 2) {
			end($array);
			$llave = key($array);
		}

		return $llave;
	}

	public function validarNrc($semana, $dia, $horas, $nrc)
	{
		for ($i = 1; $i <= count($horas); $i++) {

			if ($i % 2 != 0) {
				$inicio = $i - 1;
				continue;
			} else {

				$final = $i - 1;

				for ($j = $horas[$inicio]; $j <= $horas[$final]; $j++) {

					if (empty($semana[$dia][$j]) or $semana[$dia][$j] == $nrc) {
						$semana[$dia][$j] = $nrc;
						$valido = true;
					} else {
						$valido = false;
						break;
					}
				}

				if ($valido) {
					continue;
				} else {
					break;
				}
			}
		}

		return [$valido, $semana];
	}

	public function verificarCruce($cursos, $semana, $nrc, $nombre)
	{
		$infoNrc = $cursos[$nombre]['nrc'][$nrc];
		$listaDias = $infoNrc['dias'];
		$seccion = $infoNrc['seccion'];

		if (substr($seccion, -1) == "1" or substr($seccion, -1) == "2") {
			$resultado = true;
		} else {

			$aceptado = false;
			$ultimo_dia = CursosController::endKey($listaDias, 2);

			foreach ($listaDias as $dia => $infoDia) {

				$horas = $infoDia['horas'];
				$validarNrc = CursosController::validarNrc($semana, $dia, $horas, $nrc);
				$semana = $validarNrc[1];
				$valido = $validarNrc[0];

				if ($valido) {
					if ($dia == $ultimo_dia) {
						$aceptado = true;
					}
				} else {
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

			if ($aceptado) {
				$resultado = false;
			} else {
				$resultado = true;
			}
		}

		return $resultado;
	}

	public function nombreNrc($nrc)
	{
		$nombre = DB::select("select Nombre_asignatura from cursos where Nrc = '$nrc' limit 1")[0]->Nombre_asignatura;
		return $nombre;
	}

	public function permutacion($nombre, $perturbada, $cursos)
	{
		$listaNrc = $cursos[$nombre]['nrc'];
		$aleatorio = array_rand(array_flip(array_keys($listaNrc)));
		$listaDias = $listaNrc[$aleatorio]['dias'];
		$seccion = $listaNrc[$aleatorio]['seccion'];

		if (substr($seccion, -1) == "1" or substr($seccion, -1) == "2") {
			$resultado['continue'] = true;
		} else {

			$aceptado = false;
			$ultimo_dia = CursosController::endKey($listaDias, 2);

			foreach ($listaDias as $dia => $infoDia) {

				$horas = $infoDia['horas'];

				$validarNrc = CursosController::validarNrc($perturbada, $dia, $horas, $aleatorio);
				$perturbada = $validarNrc[1];
				$valido = $validarNrc[0];

				if ($valido) {
					if ($dia == $ultimo_dia) {
						$aceptado = true;
					}
				} else {
					foreach ($perturbada as $day => $hours) {
						foreach ($hours as $hour => $time) {
							if ($time == $aleatorio) {
								$perturbada[$day][$hour] = '';
							}
						}
					}
					break;
				}
			}

			if ($aceptado) {
				$resultado['continue'] = false;
				$resultado['aceptado'] = true;
				$resultado['perturbada'] = $perturbada;
				$resultado['aleatorio'] = $aleatorio;
			} else {
				$resultado['continue'] = true;
			}
		}
		return $resultado;
	}

	public function contarHuecos($semana)
	{
		$posiciones = [];
		$huecos = [];

		foreach ($semana as $dia => $horas) {

			$contador = 0;
			$posiciones[$dia] = [];
			foreach ($horas as $hora => $nrc) {
				$contador += 1;
				if ($nrc) {
					$posiciones[$dia][] = $contador;
				}
			}

			$huecos[$dia] = 0;
			if (count($posiciones[$dia]) > 1) {
				for ($i = $posiciones[$dia][0] + 1; $i < end($posiciones[$dia]); $i++) {
					if (!in_array($i, $posiciones[$dia])) {
						$huecos[$dia] += 1;
					}
				}
			} else {
				$huecos[$dia] = 0;
			}
		}

		return $huecos;
	}

	public function obtenerDistancias($nombres, $cursos)
	{
		foreach ($nombres as $nombre) {
			foreach ($cursos[$nombre]['nrc'] as $nrc => $info_nrc) {
				$puntos[] = ['nrc' => $nrc, 'nombre' => $nombre];
			}
		}

		foreach ($nombres as $nombre) {

			foreach ($cursos[$nombre]['nrc'] as $nrc => $info_nrc) {

				$dias = $info_nrc['dias'];

				foreach ($puntos as $punto) {

					$nrc_punto = $punto['nrc'];
					$nombre_punto = $punto['nombre'];

					if ($nombre == $nombre_punto) {
						$distancias[$nrc][$nrc_punto] = 'Son del mismo curso';
					} else {

						$huecos = [];
						$romper = false;
						$coinciden = false;

						foreach ($dias as $dia => $info_dia) {

							$espacios = [7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0, 13 => 0, 14 => 0, 15 => 0, 16 => 0, 17 => 0, 18 => 0, 19 => 0, 20 => 0];
							$estaDiaEnPunto = isset($cursos[$nombre_punto]['nrc'][$nrc_punto]['dias'][$dia]);

							if ($estaDiaEnPunto) {

								$coinciden = true;
								$horas_nrc = $info_dia['horas'];

								foreach ($horas_nrc as $hora) {
									if ($espacios[$hora] == 0 or $espacios[$hora] == $nrc) {
										$espacios[$hora] = $nrc;
									} else {

										$romper = true;
										break;
									}
								}

								if ($romper) {
									break;
								}

								$horas_punto = $cursos[$nombre_punto]['nrc'][$nrc_punto]['dias'][$dia]['horas'];

								foreach ($horas_punto as $hora) {
									if ($espacios[$hora] == 0 or $espacios[$hora] == $nrc_punto) {
										$espacios[$hora] = $nrc_punto;
									} else {
										$romper = true;
										break;
									}
								}

								if ($romper) {
									break;
								}

								$cont = 0;
								$inicial = 0;
								$final = 0;
								$empezar = false;

								foreach ($espacios as $hora => $espacio) {

									if ($espacio != 0 and $empezar == false) {

										$inicial = $espacio;
										$empezar = true;

										if ($inicial == $nrc) {
											$final = $nrc_punto;
										} else {
											$final = $nrc;
										}
									}

									if ($empezar == true and $espacio == 0) {
										$cont += 1;
									} elseif ($empezar == true and $espacio == $final) {
										$huecos[] = $cont;
										break;
									}
								}
							}
						}

						if ($romper) {
							$distancias[$nrc][$nrc_punto] = 'Se cruzan';
						} elseif (!$coinciden) {
							$distancias[$nrc][$nrc_punto] = 'No coinciden en la semana';
						} else {
							$distancia = array_sum($huecos);
							$distancias[$nrc][$nrc_punto] = $distancia;
						}
					}
				}
			}
		}

		return $distancias;
	}

	public function obtenerHeuristicasLocales($distancias)
	{
		foreach ($distancias as $nrc => $puntos) {
			foreach ($puntos as $punto => $distancia) {
				if ($distancia == 0) {
					$locales[$nrc][$punto] = 0;
				} else {
					$locales[$nrc][$punto] = 1 / $distancia;
				}
			}
		}

		return $locales;
	}

	public function obtenerMatrizFeromonas($distancias)
	{
		foreach ($distancias as $nrc => $puntos) {
			foreach ($puntos as $punto => $distancia) {
				$feromonas[$nrc][$punto] = 0;
			}
		}

		return $feromonas;
	}

	public function shuffle_assoc($array)
	{
		$keys = array_keys($array);

		shuffle($keys);

		foreach ($keys as $key) {
			$new[$key] = $array[$key];
		}

		$array = $new;

		return true;
	}

	public function aumentarFeromonas($elegidos, $distancias, $feromonas)
	{
		$costoTour = 0;

		for ($i = 0; $i < count($elegidos); $i++) {

			$nrc_actual = $elegidos[$i];

			if (end($elegidos) == $nrc_actual) {
				break;
			} else {
				$nrc_siguiente = $elegidos[$i + 1];
			}

			$dist = $distancias[$nrc_actual][$nrc_siguiente];

			if (is_string($dist)) {
				return [$feromonas, true];
			} else {
				$costoTour += $dist;
			}
		}

		for ($i = 0; $i < count($elegidos); $i++) {

			$nrc_actual = $elegidos[$i];

			if (end($elegidos) == $nrc_actual) {
				break;
			} else {
				$nrc_siguiente = $elegidos[$i + 1];
			}

			if ($costoTour != 0) {
				$feromonas[$nrc_actual][$nrc_siguiente] += 1 / $costoTour;
			}
		}

		return [$feromonas, false];
	}

	public function evaporarFeromonas($feromonas)
	{
		foreach ($feromonas as $nrc => $puntos) {
			foreach ($puntos as $punto => $feromona) {
				$feromonas[$nrc][$punto] *= 0.9;
			}
		}

		return $feromonas;
	}

	public function consultarPrerequisitos($elegidos)
	{
		$invalidos = [];
		$cadena = auth()->user()->aprobados;
		$aprobados = explode(",", $cadena);

		foreach ($elegidos as $elegido) {
			$tienePrerequisitos = DB::select("select * from prerequisitos where nombre = '$elegido'");
			if ($tienePrerequisitos) {
				$cadena = $tienePrerequisitos[0]->prerequisitos;
				$prerequisitos = explode(",", $cadena);
				$noAprobados = [];
				foreach ($prerequisitos as $prerequisito) {
					if (!in_array($prerequisito, $aprobados)) {
						$noAprobados[] = $prerequisito;
					}
				}
				if ($noAprobados) {
					$invalidos[] = [$elegido, $noAprobados];
				}
			}
		}

		return $invalidos;
	}

	public function stats_standard_deviation(array $a, $sample = true)
	{
		$n = count($a);
		if ($n === 0) {
			trigger_error("The array has zero elements", E_USER_WARNING);
			return false;
		}
		if ($sample && $n === 1) {
			trigger_error("The array has only 1 element", E_USER_WARNING);
			return false;
		}
		$mean = array_sum($a) / $n;
		$carry = 0.0;
		foreach ($a as $val) {
			$d = ((float) $val) - $mean;
			$carry += $d * $d;
		};
		if ($sample) {
			--$n;
		}
		return sqrt($carry / $n);
	}

	public function hill_climbing(Request $request)
	{
		$nombres = $request->input('nombres');
		$invalidos = CursosController::consultarPrerequisitos($nombres);

		if ($invalidos) {

			$invalido = $invalidos[0][0];
			$noAprobados = $invalidos[0][1];
			$error = "No puede elegir el curso $invalido debido a que usted no ha aprobado: ";

			$last = end($noAprobados);
			foreach ($noAprobados as $noAprobado) {

				if ($last == $noAprobado) {
					$error .= $noAprobado;
				} else {
					$error .= $noAprobado . ", ";
				}
			}

			return redirect()->back()->with('error', $error);
		}

		$cursos = CursosController::obtenerCursos();
		$semana = CursosController::obtenerSemana();
		$iteraciones = 5000;
		$cruzados = [];
		$elegidos = [];
		$start = microtime(true);

		foreach ($nombres as $nombre) {

			$listaNrc = $cursos[$nombre]['nrc'];

			foreach ($listaNrc as $nrc => $infoNrc) {

				$listaDias = $infoNrc['dias'];
				$seccion = $infoNrc['seccion'];

				if (substr($seccion, -1) == "1" or substr($seccion, -1) == "2") {
					continue;
				} else {

					$aceptado = false;
					$ultimo_nrc = CursosController::endKey($listaNrc, 1);
					$ultimo_dia = CursosController::endKey($listaDias, 2);

					foreach ($listaDias as $dia => $infoDia) {

						$horas = $infoDia['horas'];

						$validarNrc = CursosController::validarNrc($semana, $dia, $horas, $nrc);
						$semana = $validarNrc[1];
						$valido = $validarNrc[0];

						if ($valido) {
							if ($dia == $ultimo_dia) {
								$aceptado = true;
								$elegidos[] = $nrc;
							}
						} else {

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

					if ($aceptado) {
						break;
					}

					if ($nrc == $ultimo_nrc) {
						$cruzados[] = $nombre;
					}
				}
			}
		}

		if ($cruzados) {
			$error = "Los NRC de los siguientes cursos se cruzan: ";

			$last = end($cruzados);
			foreach ($cruzados as $cruzado) {

				if ($last == $cruzado) {

					$error .= $cruzado;
				} else {

					$error .= $cruzado . ", ";
				}
			}

			return redirect()->back()->with('error', $error);
		} else {

			$huequillos = [];
			while ($iteraciones > 0) {
				$perturbada = $semana;

				while (true) {
					if (count($elegidos) == 1) {
						$nrc1 = end($elegidos);
						break;
					} else {
						$nrc1 = array_rand(array_flip($elegidos));
						$nrc2 = array_rand(array_flip($elegidos));
						if ($nrc1 != $nrc2) {
							break;
						}
					}
				}

				$nombre1 = CursosController::nombreNrc($nrc1);
				if (isset($nrc2)) {
					$nombre2 = CursosController::nombreNrc($nrc2);
				}

				foreach ($perturbada as $dia => $horas) {

					foreach ($horas as $hora => $nrc) {

						if ($nrc == $nrc1) {
							$perturbada[$dia][$hora] = '';
						} elseif (isset($nrc2)) {
							if ($nrc == $nrc2) {
								$perturbada[$dia][$hora] = '';
							}
						}
					}
				}

				while (true) {

					$permutacion1 = CursosController::permutacion($nombre1, $perturbada, $cursos);
					if ($permutacion1['continue']) {
						continue;
					} else {
						if ($permutacion1['aceptado']) {
							if (!isset($nombre2)) {
								$perturbada = $permutacion1['perturbada'];
							} else {
								$perturbada1 = $permutacion1['perturbada'];
							}
							$aleatorio1 = $permutacion1['aleatorio'];
						} else {
							continue;
						}
					}

					if (isset($nombre2)) {

						$permutacion2 = CursosController::permutacion($nombre2, $perturbada1, $cursos);
						if ($permutacion2['continue']) {
							continue;
						} else {
							if ($permutacion2['aceptado']) {
								$perturbada = $permutacion2['perturbada'];
								$aleatorio2 = $permutacion2['aleatorio'];
							} else {
								continue;
							}
						}
					}

					break;
				}

				$huecos_zx = CursosController::contarHuecos($semana);
				$huecos_zxp = CursosController::contarHuecos($perturbada);

				$zx = array_sum($huecos_zx);
				$zxp = array_sum($huecos_zxp);

				if ($zxp < $zx) {
					foreach ($elegidos as $i => $elegido) {
						if ($elegido == $nrc1) {
							unset($elegidos[$i]);
						} elseif (isset($nrc2)) {
							if ($elegido == $nrc2) {
								unset($elegidos[$i]);
							}
						}
					}
					$elegidos = array_values($elegidos);

					$elegidos[] = $aleatorio1;
					if (isset($aleatorio2)) {
						$elegidos[] = $aleatorio2;
					}

					$huequillos[] = $zxp;

					$semana = $perturbada;
				}

				$iteraciones -= 1;
			}
			$end = microtime(true);
			$time = $end - $start;

			$definitivos = [];
			foreach ($elegidos as $elegido) {
				$nombre = CursosController::nombreNrc($elegido);
				$definitivos[$elegido] = $nombre;
			}

			$filas = [];
			for ($i = 7; $i <= 20; $i++) {
				foreach ($semana as $dia => $horas) {
					$filas[$i][] = $semana[$dia][$i];
				}
			}

			$sem = [];
		}

		return view('resultado', ['cursos' => $cursos, 'filas' => $filas, 'definitivos' => $definitivos, 'sem' => $sem]);
	}

	public function simulated_annealing(Request $request)
	{
		$nombres = $request->input('nombres');
		$invalidos = CursosController::consultarPrerequisitos($nombres);

		if ($invalidos) {

			$invalido = $invalidos[0][0];
			$noAprobados = $invalidos[0][1];
			$error = "No puede elegir el curso $invalido debido a que usted no ha aprobado: ";

			$last = end($noAprobados);
			foreach ($noAprobados as $noAprobado) {

				if ($last == $noAprobado) {
					$error .= $noAprobado;
				} else {
					$error .= $noAprobado . ", ";
				}
			}

			return redirect()->back()->with('error', $error);
		}

		$cursos = CursosController::obtenerCursos();
		$semana = CursosController::obtenerSemana();
		$temperatura = 5000;
		$alfa = 0.99;
		$cruzados = [];
		$elegidos = [];
		$start = microtime(true);

		foreach ($nombres as $nombre) {

			$listaNrc = $cursos[$nombre]['nrc'];

			foreach ($listaNrc as $nrc => $infoNrc) {

				$listaDias = $infoNrc['dias'];
				$seccion = $infoNrc['seccion'];

				if (substr($seccion, -1) == "1" or substr($seccion, -1) == "2") {
					continue;
				} else {

					$aceptado = false;
					$ultimo_nrc = CursosController::endKey($listaNrc, 1);
					$ultimo_dia = CursosController::endKey($listaDias, 2);

					foreach ($listaDias as $dia => $infoDia) {

						$horas = $infoDia['horas'];

						$validarNrc = CursosController::validarNrc($semana, $dia, $horas, $nrc);
						$semana = $validarNrc[1];
						$valido = $validarNrc[0];

						if ($valido) {
							if ($dia == $ultimo_dia) {
								$aceptado = true;
								$elegidos[] = $nrc;
							}
						} else {

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

					if ($aceptado) {
						break;
					}

					if ($nrc == $ultimo_nrc) {
						$cruzados[] = $nombre;
					}
				}
			}
		}

		if ($cruzados) {
			$error = "Los NRC de los siguientes cursos se cruzan: ";

			$last = end($cruzados);
			foreach ($cruzados as $cruzado) {

				if ($last == $cruzado) {

					$error .= $cruzado;
				} else {

					$error .= $cruzado . ", ";
				}
			}

			return redirect()->back()->with('error', $error);
		} else {

			while ($temperatura > 0.1) {
				$perturbada = $semana;

				while (true) {
					if (count($elegidos) == 1) {
						$nrc1 = end($elegidos);
						break;
					} else {
						$nrc1 = array_rand(array_flip($elegidos));
						$nrc2 = array_rand(array_flip($elegidos));
						if ($nrc1 != $nrc2) {
							break;
						}
					}
				}

				$nombre1 = CursosController::nombreNrc($nrc1);
				if (isset($nrc2)) {
					$nombre2 = CursosController::nombreNrc($nrc2);
				}

				foreach ($perturbada as $dia => $horas) {

					foreach ($horas as $hora => $nrc) {

						if ($nrc == $nrc1) {
							$perturbada[$dia][$hora] = '';
						} elseif (isset($nrc2)) {
							if ($nrc == $nrc2) {
								$perturbada[$dia][$hora] = '';
							}
						}
					}
				}

				while (true) {

					$permutacion1 = CursosController::permutacion($nombre1, $perturbada, $cursos);
					if ($permutacion1['continue']) {
						continue;
					} else {
						if ($permutacion1['aceptado']) {
							if (!isset($nombre2)) {
								$perturbada = $permutacion1['perturbada'];
							} else {
								$perturbada1 = $permutacion1['perturbada'];
							}
							$aleatorio1 = $permutacion1['aleatorio'];
						} else {
							continue;
						}
					}

					if (isset($nombre2)) {

						$permutacion2 = CursosController::permutacion($nombre2, $perturbada1, $cursos);
						if ($permutacion2['continue']) {
							continue;
						} else {
							if ($permutacion2['aceptado']) {
								$perturbada = $permutacion2['perturbada'];
								$aleatorio2 = $permutacion2['aleatorio'];
							} else {
								continue;
							}
						}
					}

					break;
				}

				$huecos_zx = CursosController::contarHuecos($semana);
				$huecos_zxp = CursosController::contarHuecos($perturbada);

				$zx = array_sum($huecos_zx);
				$zxp = array_sum($huecos_zxp);

				if ($zxp < $zx) {
					foreach ($elegidos as $i => $elegido) {
						if ($elegido == $nrc1) {
							unset($elegidos[$i]);
						} elseif (isset($nrc2)) {
							if ($elegido == $nrc2) {
								unset($elegidos[$i]);
							}
						}
					}
					$elegidos = array_values($elegidos);

					$elegidos[] = $aleatorio1;
					if (isset($aleatorio2)) {
						$elegidos[] = $aleatorio2;
					}

					$huequillos[] = $zxp;

					$semana = $perturbada;
					$temperatura *= $alfa;
				} else {
					$n = (float)rand() / (float)getrandmax();
					$e = M_E;
					$dz = $zxp - $zx;
					$division = - ($dz / $temperatura);
					$pdx = pow($e, $division);

					if ($n < $pdx) {
						foreach ($elegidos as $i => $elegido) {
							if ($elegido == $nrc1) {
								unset($elegidos[$i]);
							} elseif (isset($nrc2)) {
								if ($elegido == $nrc2) {
									unset($elegidos[$i]);
								}
							}
						}
						$elegidos = array_values($elegidos);

						$elegidos[] = $aleatorio1;
						if (isset($aleatorio2)) {
							$elegidos[] = $aleatorio2;
						}

						$huequillos[] = $zxp;

						$semana = $perturbada;
						$temperatura *= $alfa;
					}
				}
			}
			$end = microtime(true);
			$time = $end - $start;

			$definitivos = [];
			foreach ($elegidos as $elegido) {
				$nombre = CursosController::nombreNrc($elegido);
				$definitivos[$elegido] = $nombre;
			}

			$filas = [];
			for ($i = 7; $i <= 20; $i++) {
				foreach ($semana as $dia => $horas) {
					$filas[$i][] = $semana[$dia][$i];
				}
			}

			$sem = [];
		}

		return view('resultado', ['cursos' => $cursos, 'filas' => $filas, 'definitivos' => $definitivos, 'sem' => $sem]);
	}

	public function ant_colony(Request $request)
	{
		$nombres = $request->input('nombres');
		$invalidos = CursosController::consultarPrerequisitos($nombres);

		if ($invalidos) {

			$invalido = $invalidos[0][0];
			$noAprobados = $invalidos[0][1];
			$error = "No puede elegir el curso $invalido debido a que usted no ha aprobado: ";

			$last = end($noAprobados);
			foreach ($noAprobados as $noAprobado) {

				if ($last == $noAprobado) {
					$error .= $noAprobado;
				} else {
					$error .= $noAprobado . ", ";
				}
			}

			return redirect()->back()->with('error', $error);
		}

		$start = microtime(true);
		$cursos = CursosController::obtenerCursos();
		$distancias = CursosController::obtenerDistancias($nombres, $cursos);
		$locales = CursosController::obtenerHeuristicasLocales($distancias);
		$feromonas = CursosController::obtenerMatrizFeromonas($distancias);
		$iteraciones = 5000;
		$repeticiones = 5000;
		$resultados = [];
		$numero_elegidos = count($nombres);
		$huecos = 0;
		$elegidos_def = [];
		$semana_def = [];
		$cadenas = true;

		while ($cadenas == true) {

			$semana = CursosController::obtenerSemana();
			$cruzados = [];
			$elegidos = [];

			foreach ($nombres as $nombre) {

				$listaNrc = $cursos[$nombre]['nrc'];

				foreach ($listaNrc as $nrc => $infoNrc) {

					$listaDias = $infoNrc['dias'];
					$seccion = $infoNrc['seccion'];

					if (substr($seccion, -1) == "1" or substr($seccion, -1) == "2") {
						continue;
					} else {

						$aceptado = false;
						$ultimo_nrc = CursosController::endKey($listaNrc, 1);
						$ultimo_dia = CursosController::endKey($listaDias, 2);

						foreach ($listaDias as $dia => $infoDia) {

							$horas = $infoDia['horas'];

							$validarNrc = CursosController::validarNrc($semana, $dia, $horas, $nrc);
							$semana = $validarNrc[1];
							$valido = $validarNrc[0];

							if ($valido) {
								if ($dia == $ultimo_dia) {
									$aceptado = true;
									$elegidos[] = $nrc;
								}
							} else {
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

						if ($aceptado) {
							break;
						}

						if ($nrc == $ultimo_nrc) {
							$cruzados[] = $nombre;
						}
					}
				}
			}

			if ($cruzados) {
				shuffle($nombres);
				continue;
			}

			$feroArreglo = CursosController::aumentarFeromonas($elegidos, $distancias, $feromonas);
			$feromonas = $feroArreglo[0];
			$cadenas = $feroArreglo[1];

			if ($cadenas) {
				shuffle($nombres);
			}
		}

		if ($cruzados) {
			$error = "Los NRC de los siguientes cursos se cruzan: ";

			$last = end($cruzados);
			foreach ($cruzados as $cruzado) {

				if ($last == $cruzado) {

					$error .= $cruzado;
				} else {

					$error .= $cruzado . ", ";
				}
			}

			return redirect()->back()->with('error', $error);
		} else {

			$huequillos = [];
			$semana_inicial = $semana;

			while ($iteraciones > 0) {
				$perturbada = $semana;

				while (true) {
					if (count($elegidos) == 1) {
						$nrc1 = end($elegidos);
						break;
					} else {
						$nrc1 = array_rand(array_flip($elegidos));
						$nrc2 = array_rand(array_flip($elegidos));
						if ($nrc1 != $nrc2) {
							break;
						}
					}
				}

				$nombre1 = CursosController::nombreNrc($nrc1);
				if (isset($nrc2)) {
					$nombre2 = CursosController::nombreNrc($nrc2);
				}

				foreach ($perturbada as $dia => $horas) {

					foreach ($horas as $hora => $nrc) {

						if ($nrc == $nrc1) {
							$perturbada[$dia][$hora] = '';
						} elseif (isset($nrc2)) {
							if ($nrc == $nrc2) {
								$perturbada[$dia][$hora] = '';
							}
						}
					}
				}

				while (true) {

					$permutacion1 = CursosController::permutacion($nombre1, $perturbada, $cursos);
					if ($permutacion1['continue']) {
						continue;
					} else {
						if ($permutacion1['aceptado']) {
							if (!isset($nombre2)) {
								$perturbada = $permutacion1['perturbada'];
							} else {
								$perturbada1 = $permutacion1['perturbada'];
							}
							$aleatorio1 = $permutacion1['aleatorio'];
						} else {
							continue;
						}
					}

					if (isset($nombre2)) {

						$permutacion2 = CursosController::permutacion($nombre2, $perturbada1, $cursos);
						if ($permutacion2['continue']) {
							continue;
						} else {
							if ($permutacion2['aceptado']) {
								$perturbada = $permutacion2['perturbada'];
								$aleatorio2 = $permutacion2['aleatorio'];
							} else {
								continue;
							}
						}
					}

					break;
				}

				$elegidos_nuevos = $elegidos;

				foreach ($elegidos_nuevos as $i => $elegido) {
					if ($elegido == $nrc1) {
						unset($elegidos_nuevos[$i]);
					} elseif (isset($nrc2)) {
						if ($elegido == $nrc2) {
							unset($elegidos_nuevos[$i]);
						}
					}
				}
				$elegidos_nuevos = array_values($elegidos_nuevos);

				$elegidos_nuevos[] = $aleatorio1;
				if (isset($aleatorio2)) {
					$elegidos_nuevos[] = $aleatorio2;
				}

				$feroArreglo = CursosController::aumentarFeromonas($elegidos_nuevos, $distancias, $feromonas);
				$feromonas = $feroArreglo[0];
				$cadenas = $feroArreglo[1];

				if ($cadenas) {
					continue;
				}

				$elegidos = $elegidos_nuevos;
				$semana = $perturbada;

				$iteraciones -= 1;
			}

			foreach ($nombres as $nombre) {
				foreach ($cursos[$nombre]['nrc'] as $nrc => $info_nrc) {
					$puntos[] = ['nrc' => $nrc, 'nombre' => $nombre];
				}
			}

			while ($repeticiones > 0) {
				$elegidos = [];
				$nombresX = [];
				$descartados = [];

				$semana = CursosController::obtenerSemana();
				$nombreRamdom = array_rand(array_flip($nombres));
				$nrcRamdom = array_rand(array_flip(array_keys($cursos[$nombreRamdom]['nrc'])));

				$infoNrc = $cursos[$nombreRamdom]['nrc'][$nrcRamdom];
				$listaDias = $infoNrc['dias'];
				$seccion = $infoNrc['seccion'];

				if (substr($seccion, -1) == "1" or substr($seccion, -1) == "2") {
					continue;
				} else {

					$ultimo_dia = CursosController::endKey($listaDias, 2);

					foreach ($listaDias as $dia => $infoDia) {

						$horas = $infoDia['horas'];
						$validarNrc = CursosController::validarNrc($semana, $dia, $horas, $nrcRamdom);
						$semana = $validarNrc[1];

						if ($dia == $ultimo_dia) {
							$elegidos[] = $nrcRamdom;
						}
					}
				}

				$nombresX[] = $nombreRamdom;
				$descartados[] = $nrcRamdom;

				for ($i = 0; $i < count($nombres); $i++) {

					$numeradores = [];
					$probabilidades = [];
					$acomuladas = [];

					foreach ($puntos as $punto) {

						if (in_array($punto['nombre'], $nombresX)) {
							if (!in_array($punto['nrc'], $descartados)) {
								$descartados[] = $punto['nrc'];
							}
						} else {

							$estadoCruce = CursosController::verificarCruce($cursos, $semana, $punto['nrc'], $punto['nombre']);

							if ($estadoCruce) {
								if (!in_array($punto['nrc'], $descartados)) {
									$descartados[] = $punto['nrc'];
								}
							} else {
								$ultimoElegido = end($elegidos);
								$valorFeromona = $feromonas[$ultimoElegido][$punto['nrc']];
								$valorLocal = $locales[$ultimoElegido][$punto['nrc']];
								$numerador = $valorFeromona * $valorLocal;

								$numeradores[$punto['nrc']] = $numerador;
							}
						}
					}

					$denominador = array_sum($numeradores);

					foreach ($numeradores as $candidato => $numerador) {

						if ($denominador == 0) {
							$probabilidad = 0;
						} else {
							$probabilidad = $numerador / $denominador;
						}

						$probabilidades[$candidato] = $probabilidad;
					}

					$suma = 0;
					foreach ($probabilidades as $candidato => $probabilidad) {
						$suma += $probabilidad;
						$acomuladas[$candidato] = $suma;
					}

					$n = (float)rand() / (float)getrandmax();

					foreach ($acomuladas as $candidato => $acomulado) {

						if ($n < $acomulado) {

							$name = CursosController::nombreNrc($candidato);
							$infoNrc = $cursos[$name]['nrc'][$candidato];
							$listaDias = $infoNrc['dias'];
							$seccion = $infoNrc['seccion'];

							if (substr($seccion, -1) == "1" or substr($seccion, -1) == "2") {
								continue;
							} else {

								$ultimo_dia = CursosController::endKey($listaDias, 2);
								foreach ($listaDias as $dia => $infoDia) {

									$horas = $infoDia['horas'];
									$validarNrc = CursosController::validarNrc($semana, $dia, $horas, $candidato);
									$semana = $validarNrc[1];

									if ($dia == $ultimo_dia) {
										$elegidos[] = $candidato;
									}
								}
							}

							if (!in_array($name, $nombresX)) {
								$nombresX[] = $name;
							}

							if (!in_array($candidato, $descartados)) {
								$descartados[] = $candidato;
							}

							break;
						}
					}
				}

				$feroArreglo = CursosController::aumentarFeromonas($elegidos, $distancias, $feromonas);
				$feromonas = $feroArreglo[0];
				$cadenas = $feroArreglo[1];

				if ($cadenas) {
					continue;
				}

				$feromonas = CursosController::evaporarFeromonas($feromonas);

				$arrayHuecos = CursosController::contarHuecos($semana);
				$huecosNuevos = array_sum($arrayHuecos);

				if ($numero_elegidos != 1) {
					if (count($elegidos) == $numero_elegidos) {
						if ($huecos == 0) {
							$huecos = $huecosNuevos;
							$elegidos_def = $elegidos;
							$semana_def = $semana;
						} elseif ($huecosNuevos < $huecos) {
							$huecos = $huecosNuevos;
							$elegidos_def = $elegidos;
							$semana_def = $semana;
						}
					}
				} else {
					$elegidos_def = $elegidos;
					$semana_def = $semana;
				}

				$repeticiones -= 1;
			}

			$semana = $semana_def;
			$elegidos = $elegidos_def;

			$end = microtime(true);
			$time = $end - $start;

			$definitivos = [];
			foreach ($elegidos as $elegido) {
				$nombre = CursosController::nombreNrc($elegido);
				$definitivos[$elegido] = $nombre;
			}

			$filas = [];
			for ($i = 7; $i <= 20; $i++) {
				foreach ($semana as $dia => $horas) {
					$filas[$i][] = $semana[$dia][$i];
				}
			}

			$sem = [];
		}

		return view('resultado', ['cursos' => $cursos, 'filas' => $filas, 'definitivos' => $definitivos, 'sem' => $sem]);
	}
}
