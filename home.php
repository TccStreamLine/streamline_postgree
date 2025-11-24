<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Home - RELP!</title>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <a href="#" class="logo">
            <img src="img/relplogo.png" alt="RELP! Logo">
        </a>
        
        <nav class="nav-menu">
            <ul class="nav-links">
                <li><a href="#inicio">Início</a></li>
                <li><a href="#quem-somos">Quem somos?</a></li>
                <li><a href="#servicos">Serviços</a></li>
                <li><a href="#faq">FAQ</a></li>
            </ul>
        </nav>
        
        <div class="auth-buttons">
            <a href="login.php" class="btn-login">Login</a>
            <a href="formulario.php" class="btn-cadastro">Cadastro</a>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="inicio" class="hero">
        <div class="hero-content">
            <h1 class="hero-title">RELP!<br>FOR YOUR COMPANY</h1>
            <p class="hero-subtitle">Como podemos ajudar você a crescer juntamente de sua microempresa?</p>
            <a href="formulario.php" class="hero-cta">Assine já</a>
        </div>
        <div class="hero-image">
            <img src="img/imagemhomecomecosemfundo.png" alt="Ilustração RELP!">
        </div>
    </section>

    <!-- Quem Somos Section -->
        <!-- Quem Somos Section -->
    <section id="quem-somos" class="section quem-somos">
        <div class="section-center">
            <h2 class="section-title quem-somos-title">Quem somos?</h2>
            <p class="section-subtitle quem-somos-subtitle">Como a Streamline pode te ajudar via RELP!</p>
        </div>
        
        <div class="quem-somos-content">
            <div class="streamline-logo-container">
                <img src="img/logostreamline.png" alt="StreamLine Logo" class="streamline-logo">
            </div>
            <div class="streamline-info">
                <h3 class="streamline-title">Nossa equipe de desenvolvedores vem da startup StreamLine, que projetou a RELP! para facilitar sua vida.</h3>
                <p class="streamline-description">Nossa equipe de desenvolvedores vem da startup StreamLine, que projetou a RELP! para facilitar sua vida.</p>
                <p class="streamline-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed sit amet justo ipsum. Sed accumsan quam vitae est varius fringilla. Pellentesque placerat vestibulum lorem sed porta. .</p>
            </div>
        </div>
        
        <!-- Funcionalidades dentro da seção Quem Somos -->
        <div class="section-center funcionalidades-section">
            <h2 class="section-title">Nossas principais funcionalidades</h2>
            <p class="section-subtitle">Idealizadas e programadas pensando nas suas necessidades diárias como MEI, CEO, funcionário e até fornecedor de uma micro empresa nos dias atuais...</p>
            
            <div class="funcionalidades-grid">
                <div class="funcionalidade-card">
                    <img src="img/image 18.png" alt="Sistema de estoque inteligente">
                    <h3>Sistema de estoque inteligente</h3>
                    <p>Controle completo do seu inventário com alertas automáticos de reposição</p>
                </div>
                
                <div class="funcionalidade-card">
                    <img src="img/image 19.png" alt="Aba para vendedores">
                    <h3>Aba para vendedores e prestadores de serviços</h3>
                    <p>Interface dedicada para gerenciar suas vendas e prestações de serviços</p>
                </div>
                
                <div class="funcionalidade-card">
                    <img src="img/image 20.png" alt="Agenda de uso simples">
                    <h3>Agenda de uso simples e útil para a empresa</h3>
                    <p>Organize seus compromissos e tarefas de forma intuitiva</p>
                </div>
                
                <div class="funcionalidade-card">
                    <img src="img/image 21.png" alt="Interface gráfica">
                    <h3>Uma interface gráfica de caixa para facilitar o início de suas vendas</h3>
                    <p>Sistema de PDV moderno e fácil de usar para suas transações</p>
                </div>
                
                <div class="funcionalidade-card">
                    <img src="img/image 22.png" alt="Dashboard pensado">
                    <h3>Dashboard pensado para a visualização da sua empresa no dia a dia, buscando a melhora geral</h3>
                    <p>Relatórios visuais para acompanhar o crescimento do seu negócio</p>
                </div>
                
                <div class="funcionalidade-card">
                    <img src="img/image 23.png" alt="Sistema de acesso">
                    <h3>Sistema de acesso a fornecedores para notificações inteligentes de baixa em estoque</h3>
                    <p>Mantenha seus fornecedores sempre informados automaticamente</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Planos Section -->
    <section id="servicos" class="section section-center white-background">
        <h2 class="section-title">Planos disponíveis para compra</h2>
        <p class="section-subtitle">Pensados de forma acessível para atender suas principais necessidades de forma prática e democrática.</p>
        
        <div class="planos-grid">
            <div class="plano-card">
                <div class="plano-badge">Starter</div>
                <div class="plano-header">
                    <h3 class="plano-nome">Starter</h3>
                    <p class="plano-descricao">Acesso a 7 dias de teste gratuito do sistema de gerenciamento de micro e pequenas empresas.</p>
                </div>
                <div class="plano-preco">
                    <span class="preco">Grátis</span>
                </div>
            </div>
            
            <div class="plano-card plano-destaque">
                <div class="plano-badge">Pro</div>
                <div class="plano-header">
                    <h3 class="plano-nome">Pro</h3>
                    <p class="plano-descricao">Acesso mensal ao sistema de gerenciamento somente com sua versão web.</p>
                </div>
                <div class="plano-preco">
                    <span class="preco">R$49,90</span>
                    <span class="periodo">/ mês</span>
                </div>
            </div>
            
            <div class="plano-card">
                <div class="plano-badge">Business+</div>
                <div class="plano-header">
                    <h3 class="plano-nome">Business+</h3>
                    <p class="plano-descricao">Acesso ao sistema de gerenciamento web + um aplicativo para CEOs focado na visualização rápida de informações.</p>
                </div>
                <div class="plano-preco">
                    <span class="preco">R$74,90</span>
                    <span class="periodo">/ mês</span>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="section section-center faq-section">
        <h2 class="section-title">FAQ - Perguntas Frequentes</h2>
        <p class="section-subtitle">Tire suas principais dúvidas sobre o RELP!</p>
        
        <div class="faq-container">
            <div class="faq-item">
                <h3 class="faq-question">Como funciona o RELP!?</h3>
                <p class="faq-answer">O RELP! é uma plataforma completa de gestão empresarial que integra estoque, vendas, agenda e dashboard em um só lugar.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">Quanto custa?</h3>
                <p class="faq-answer">Oferecemos planos acessíveis pensados para atender diferentes necessidades empresariais.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">É seguro?</h3>
                <p class="faq-answer">Sim! Utilizamos as melhores práticas de segurança para proteger seus dados e informações empresariais.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <div class="footer-logo">
                    <span class="footer-brand-text">Relp!</span>
                </div>
                <p class="footer-copyright">
                    Copyright © 2025 Streamline ltd.<br>
                    All rights reserved
                </p>
                <div class="footer-social">
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-dribbble"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-info">
                <div class="footer-section">
                    <h4 class="footer-title">Contatos para suporte</h4>
                    <div class="footer-contact">
                        <p><strong>E-mail: contato@streamlineapp.com</strong></p>
                        <p><strong>Telefone: 123456789</strong></p>
                        <p><strong>Endereço: Rua dos bobos N 0</strong></p>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">Nos mande um E-mail!</h4>
                    <div class="newsletter">
                        <input type="email" placeholder="Your email address" class="newsletter-input">
                        <button class="newsletter-btn"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="home.js"></script>
</body>
</html>
