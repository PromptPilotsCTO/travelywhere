import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'dart:math' as math;

class RouteMapDemo extends StatefulWidget {
  const RouteMapDemo({super.key});

  @override
  State<RouteMapDemo> createState() => _RouteMapDemoState();
}

class _RouteMapDemoState extends State<RouteMapDemo>
    with SingleTickerProviderStateMixin {
  // 👉 Deine echten Koordinaten hier einsetzen
  final LatLng pointA = const LatLng(50.8030, 6.4820); // Düren (Beispiel)
  final LatLng pointB = const LatLng(50.7374, 7.0982); // Bonn (Beispiel)

  late final AnimationController _ctrl;
  late final Animation<double> _t; // 0.0 → 1.0 Fortschritt

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 6),
    );

    _t = CurvedAnimation(parent: _ctrl, curve: Curves.easeInOutCubic);

    // Bei jeder Frame-Änderung neu zeichnen.
    _ctrl.addListener(() => setState(() {}));

    // Endlos-Schleife (A→B→A…): Wenn du nur einmal A→B willst, nimm _ctrl.forward();
    _ctrl.forward();
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  LatLng _midpoint(LatLng a, LatLng b) =>
      LatLng((a.latitude + b.latitude) / 2, (a.longitude + b.longitude) / 2);

  // Linearer Lerp zwischen A und B
  LatLng _lerp(LatLng a, LatLng b, double t) {
    final lat = a.latitude + (b.latitude - a.latitude) * t;
    final lng = a.longitude + (b.longitude - a.longitude) * t;
    return LatLng(lat, lng);
  }

  // Peilung (Bearing) in Radiant – für die Rotation des Busses
  double _bearing(LatLng from, LatLng to) {
    final lat1 = from.latitude * math.pi / 180.0;
    final lat2 = to.latitude * math.pi / 180.0;
    final dLng = (to.longitude - from.longitude) * math.pi / 180.0;

    final y = math.sin(dLng) * math.cos(lat2);
    final x = math.cos(lat1) * math.sin(lat2) -
        math.sin(lat1) * math.cos(lat2) * math.cos(dLng);
    return math.atan2(y, x); // Radiant
  }

  @override
  Widget build(BuildContext context) {
    final latDiff = (pointA.latitude - pointB.latitude).abs();
    final lngDiff = (pointA.longitude - pointB.longitude).abs();
    final maxDiff = math.max(latDiff, lngDiff);

    // Einfache Heuristik: je größer die Distanz, desto kleiner der Zoom
    double zoom;
    if (maxDiff < 0.05) {
      zoom = 12;
    } else if (maxDiff < 0.1) {
      zoom = 11;
    } else if (maxDiff < 0.5) {
      zoom = 9;
    } else if (maxDiff < 1.0) {
      zoom = 7;
    } else {
      zoom = 5;
    }

    final center = _midpoint(pointA, pointB);

    // Aktuelle Position des VW-Busses auf der Strecke
    final current = _lerp(pointA, pointB, _t.value);

    // Linie wächst von A bis zur aktuellen Position
    final routeVisible = <LatLng>[pointA, current];

    // Für die Rotation: Richtung vom letzten kleinen Schritt zur aktuellen Position
    final tinyStepBack = _lerp(pointA, pointB, (_t.value - 0.001).clamp(0.0, 1.0));
    final angle = _bearing(tinyStepBack, current); // Radiant

    return Scaffold(
      appBar: AppBar(title: const Text('A → B (animiert)')),
      body: FlutterMap(
        options: MapOptions(
          initialCenter: center,
          initialZoom: 9,
        ),
        children: [
          TileLayer(
            urlTemplate: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            subdomains: const ['a', 'b', 'c'],
            userAgentPackageName: 'com.example.app',
          ),

          // Wachsende Route
          PolylineLayer(
            polylines: [
              Polyline(
                points: routeVisible,
                strokeWidth: 5,
                color: Colors.blueAccent,
              ),
            ],
          ),

          // Marker A & B
          MarkerLayer(
            markers: [
              Marker(
                point: pointA,
                width: 40,
                height: 40,
                child: const Icon(Icons.location_on, size: 36),
              ),
              Marker(
                point: pointB,
                width: 40,
                height: 40,
                child: const Icon(Icons.flag, size: 32),
              ),
            ],
          ),

          // Fahrender VW-Bus
          MarkerLayer(
            markers: [
              Marker(
                point: current,
                width: 56,
                height: 56,
                child: Transform.rotate(
                  // Falls dein PNG nach oben zeigt, passt 'angle' direkt.
                  // Wenn es nach rechts zeigt, nimm: angle + math.pi / 2
                  angle: angle,
                  child: Image.asset(
                    'assets/icons/vw_bus.png',
                    width: 48,
                    height: 48,
                    filterQuality: FilterQuality.high,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
