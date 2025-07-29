import 'package:flutter/material.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:geolocator/geolocator.dart';
import 'dart:async';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Sys GCM',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.deepPurple),
      ),
      home: const HomePage(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  Timer? _locationTimer;
  bool _enviando = false;
  String _status = '';
  String? _usuarioLogado;

  @override
  void initState() {
    super.initState();
    _requestPermissions();
    _startEnvioLocalizacao();
  }

  @override
  void dispose() {
    _locationTimer?.cancel();
    super.dispose();
  }

  Future<void> _requestPermissions() async {
    await [
      Permission.location,
      Permission.locationWhenInUse,
      Permission.locationAlways,
    ].request();
  }

  Future<void> _startEnvioLocalizacao() async {
    _locationTimer?.cancel();
    _locationTimer = Timer.periodic(const Duration(seconds: 10), (_) async {
      try {
        bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
        LocationPermission permission = await Geolocator.checkPermission();
        if (!serviceEnabled || permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
          setState(() { _status = 'Permissão de localização não concedida.'; });
          return;
        }
        Position pos = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
        setState(() { _enviando = true; _status = 'Enviando localização...'; });
        final body = '{"login":"${_usuarioLogado ?? ''}","latitude":${pos.latitude},"longitude":${pos.longitude}}';
        print('Enviando localização: $body');
        final response = await http.post(
          Uri.parse('https://8cca5c4766eb.ngrok-free.app/sys.gcm/frontend/salvar_localizacao.php'),
          headers: {'Content-Type': 'application/json'},
          body: body,
        );
        print('Status: ${response.statusCode}');
        print('Body: ${response.body}');
        setState(() { _enviando = false; _status = 'Localização enviada!'; });
      } catch (e) {
        setState(() { _enviando = false; _status = 'Erro ao enviar localização.'; });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFE3F0FF),
      appBar: AppBar(title: const Text('Sys GCM')),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.location_on, size: 64, color: Colors.blue),
            const SizedBox(height: 16),
            Text(_status, style: const TextStyle(fontSize: 16)),
            const SizedBox(height: 32),
            ElevatedButton(
              onPressed: () {
                // Solicitar login do usuário antes de abrir o WebView
                showDialog(
                  context: context,
                  builder: (context) {
                    final controller = TextEditingController();
                    return AlertDialog(
                      title: const Text('Digite seu login'),
                      content: TextField(
                        controller: controller,
                        decoration: const InputDecoration(labelText: 'Login'),
                      ),
                      actions: [
                        TextButton(
                          onPressed: () {
                            Navigator.of(context).pop();
                          },
                          child: const Text('Cancelar'),
                        ),
                        ElevatedButton(
                          onPressed: () {
                            setState(() {
                              _usuarioLogado = controller.text.trim();
                            });
                            Navigator.of(context).pop();
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => const WebViewPage(url: 'https://8cca5c4766eb.ngrok-free.app/sys.gcm/frontend/dashboard.php'),
                              ),
                            );
                          },
                          child: const Text('Acessar'),
                        ),
                      ],
                    );
                  },
                );
              },
              child: const Text('Acessar Sistema Web Completo'),
            ),
          ],
        ),
      ),
    );
  }
}

class WebViewPage extends StatefulWidget {
  final String url;
  const WebViewPage({super.key, required this.url});

  @override
  State<WebViewPage> createState() => _WebViewPageState();
}

class _WebViewPageState extends State<WebViewPage> {
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..loadRequest(Uri.parse(widget.url));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sys GCM')),
      body: WebViewWidget(controller: _controller),
    );
  }
}
