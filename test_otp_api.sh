#!/bin/bash

# Script de test pour l'API OTP - Estuaire Travel
# Usage: ./test_otp_api.sh

echo "==================================="
echo "   TEST API OTP - Estuaire Travel"
echo "==================================="
echo ""

# Configuration
API_URL="http://localhost:8001/api"
PHONE="670000000"  # Changez ce numéro pour tester

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}📱 Numéro de test: ${PHONE}${NC}"
echo ""

# Test 1: Envoyer un OTP
echo -e "${BLUE}Test 1: Envoi d'un OTP${NC}"
echo "-----------------------------------"
echo "Request: POST ${API_URL}/otp/send"
echo ""

RESPONSE=$(curl -s -X POST "${API_URL}/otp/send" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"${PHONE}\"}")

echo "Response:"
echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
echo ""

# Vérifier si l'envoi a réussi
if echo "$RESPONSE" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ OTP envoyé avec succès${NC}"
else
  echo -e "${RED}✗ Échec de l'envoi OTP${NC}"
fi
echo ""
echo ""

# Test 2: Vérifier un OTP (exemple avec un mauvais code)
echo -e "${BLUE}Test 2: Vérification d'un OTP incorrect${NC}"
echo "-----------------------------------"
echo "Request: POST ${API_URL}/otp/verify"
echo ""

RESPONSE=$(curl -s -X POST "${API_URL}/otp/verify" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"${PHONE}\",\"otp\":\"000000\"}")

echo "Response:"
echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q '"success":false'; then
  echo -e "${GREEN}✓ Erreur attendue pour un code incorrect${NC}"
else
  echo -e "${RED}✗ Comportement inattendu${NC}"
fi
echo ""
echo ""

# Test 3: Demander le vrai code OTP
echo -e "${BLUE}Test 3: Mode interactif${NC}"
echo "-----------------------------------"
echo -e "${BLUE}Entrez le code OTP reçu par SMS (ou appuyez sur Entrée pour passer):${NC}"
read -r OTP_CODE

if [ -n "$OTP_CODE" ]; then
  echo ""
  echo "Vérification du code: ${OTP_CODE}"

  RESPONSE=$(curl -s -X POST "${API_URL}/otp/verify" \
    -H "Content-Type: application/json" \
    -d "{\"phone\":\"${PHONE}\",\"otp\":\"${OTP_CODE}\"}")

  echo "Response:"
  echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
  echo ""

  if echo "$RESPONSE" | grep -q '"success":true'; then
    echo -e "${GREEN}✓ Numéro vérifié avec succès !${NC}"
  else
    echo -e "${RED}✗ Code incorrect ou expiré${NC}"
  fi
else
  echo "Test interactif ignoré."
fi
echo ""
echo ""

# Test 4: Renvoyer un OTP
echo -e "${BLUE}Test 4: Renvoyer un OTP${NC}"
echo "-----------------------------------"
echo "Request: POST ${API_URL}/otp/resend"
echo ""

RESPONSE=$(curl -s -X POST "${API_URL}/otp/resend" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"${PHONE}\"}")

echo "Response:"
echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ Nouveau OTP envoyé${NC}"
else
  echo -e "${RED}✗ Échec du renvoi${NC}"
fi
echo ""
echo ""

echo "==================================="
echo "   Tests terminés"
echo "==================================="
echo ""
echo -e "${BLUE}💡 Tips:${NC}"
echo "- Pour tester avec un vrai numéro, modifiez la variable PHONE dans ce script"
echo "- Assurez-vous que le serveur Laravel tourne sur le port 8001"
echo "- Les codes OTP expirent après 10 minutes"
echo ""
