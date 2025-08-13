import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'screens/login_screen.dart';
import 'widgets/iphone_frame_wrapper.dart';
import 'route_map_demo.dart';


void main() {
  runApp(TravelywhereApp());
}

class TravelywhereApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Travelywhere',
      theme: ThemeData(
        primaryColor: Color(0xFF182146),
        scaffoldBackgroundColor: Color(0xFFDDEEFC),
      ),
      home: LoginScreen(),
      // home: const RouteMapDemo(),
      builder: (context, child) {
        final isDesktop = kIsWeb && MediaQuery.of(context).size.width > 800;
        if (child == null) return const SizedBox.shrink();

        return isDesktop
            ? IPhoneFrameWrapper(child: child)
            : child;
      },
    );
  }
}
